<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Events\FailedPayment;
use App\Events\Payment;
use App\Helpers\Functions;
use App\Http\Requests\TransactionCreateRequest;
use App\Http\Requests\TransactionDeleteRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Http\Requests\TransactionOverrideStatusRequest;
use App\Http\Requests\TransactionRefundRequest;
use App\Http\Requests\TransactionBulkReviewRequest;
use App\Http\Requests\TransactionBulkExportRequest;
use App\Models\Transaction;
use App\Models\TransactionAuditLog;
use App\Jobs\FailPendingTransaction;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentSuccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    // -------------------------------------------------------------------------
    // LIST
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $transactions = Transaction::applyFilters()
            ->customerFilterable()
            ->with(['user', 'order'])
            ->paginate($request->integer('per_page', 25));

        return ['transactions' => $transactions];
    }

    // -------------------------------------------------------------------------
    // DETAIL
    // -------------------------------------------------------------------------

    public function show(string $transaction_uuid)
    {
        $transaction = Transaction::withRelations()
            ->find($transaction_uuid);

        return ['transaction' => $transaction];
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    public function store(TransactionCreateRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['type']    = 'PAYMENT';

        $uuid        = Str::uuid()->toString();
        $data['uuid'] = $uuid;

        $token        = request()->header('Authorization');
        $redirect_url = Functions::get_order_detail_page_url($request->order_uuid);
        $payment_url  = "http://127.0.0.1:5500/index.html?amount={$request->amount}&transaction_uuid={$uuid}&token={$token}&redirect_url={$redirect_url}";

        $data['payment_url'] = urlencode($payment_url);

        // Extract a searchable reference from informations if present
        if (!empty($data['informations']['reference'])) {
            $data['payment_reference'] = $data['informations']['reference'];
        }

        $transaction = Transaction::create($data);
        $transaction->payment_url = $payment_url;

        FailPendingTransaction::dispatch($transaction->uuid)->delay(now()->addMinutes(10));

        Payment::dispatchIf($transaction->status === TransactionStatus::SUCCESS->value, $transaction->order, $transaction);
        FailedPayment::dispatchIf($transaction->status === TransactionStatus::FAILED->value, $transaction->order, $transaction);

        return ['transaction' => $transaction];
    }

    // -------------------------------------------------------------------------
    // UPDATE (gateway callback — status + informations only)
    // -------------------------------------------------------------------------

    public function update(TransactionUpdateRequest $request, Transaction $transaction)
    {
        $oldStatus = $transaction->status;
        $data      = $request->validated();

        // Keep payment_reference in sync if informations are updated
        if (!empty($data['informations']['reference'])) {
            $data['payment_reference'] = $data['informations']['reference'];
        }

        $transaction->update($data);

        // Log automated status transitions (gateway callbacks)
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            TransactionAuditLog::create([
                'transaction_uuid' => $transaction->uuid,
                'performed_by'     => null, // system/gateway
                'action'           => 'status_updated',
                'old_value'        => $oldStatus,
                'new_value'        => $data['status'],
                'reason'           => 'Gateway callback',
                'metadata'         => ['ip' => request()->ip()],
            ]);
        }

        Payment::dispatchIf($transaction->status === TransactionStatus::SUCCESS->value, $transaction->order, $transaction);
        FailedPayment::dispatchIf($transaction->status === TransactionStatus::FAILED->value, $transaction->order, $transaction);

        return ['transaction' => $transaction];
    }

    // -------------------------------------------------------------------------
    // DELETE (soft-delete, admin only)
    // -------------------------------------------------------------------------

    public function destroy(TransactionDeleteRequest $request)
    {
        $ids     = $request->transaction_ids;
        $deleted = Transaction::whereIn('id', $ids)->delete();

        // Audit every soft-deleted transaction
        $transactions = Transaction::withTrashed()->whereIn('id', $ids)->get();

        foreach ($transactions as $t) {
            TransactionAuditLog::create([
                'transaction_uuid' => $t->uuid,
                'performed_by'     => auth()->id(),
                'action'           => 'soft_deleted',
                'reason'           => $request->input('reason'),
                'metadata'         => ['ip' => request()->ip()],
            ]);
        }

        return ['deleted' => $deleted];
    }

    // -------------------------------------------------------------------------
    // MANUAL STATUS OVERRIDE  (admin only — logs every change)
    // -------------------------------------------------------------------------

    public function overrideStatus(TransactionOverrideStatusRequest $request, Transaction $transaction)
    {
        $oldStatus = $transaction->status;
        $newStatus = $request->status;

        $transaction->update(['status' => $newStatus]);

        TransactionAuditLog::create([
            'transaction_uuid' => $transaction->uuid,
            'performed_by'     => auth()->id(),
            'action'           => 'status_override',
            'old_value'        => $oldStatus,
            'new_value'        => $newStatus,
            'reason'           => $request->reason,
            'metadata'         => [
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);

        // Fire domain events if the new status warrants it
        Payment::dispatchIf($newStatus === TransactionStatus::SUCCESS->value, $transaction->order, $transaction);
        FailedPayment::dispatchIf($newStatus === TransactionStatus::FAILED->value, $transaction->order, $transaction);

        return ['transaction' => $transaction->fresh()];
    }

    // -------------------------------------------------------------------------
    // REFUND  (creates a new REFUND-type transaction linked to the original)
    // -------------------------------------------------------------------------

    public function refund(TransactionRefundRequest $request, Transaction $transaction)
    {
        $refund = Transaction::create([
            'uuid'                     => Str::uuid()->toString(),
            'user_id'                  => $transaction->user_id,
            'order_uuid'               => $transaction->order_uuid,
            'method'                   => $transaction->method,
            'amount'                   => $request->amount ?? $transaction->amount,
            'status'                   => TransactionStatus::PENDING->value,
            'type'                     => 'REFUND',
            'parent_transaction_uuid'  => $transaction->uuid,
            'informations'             => ['reason' => $request->reason],
            'payment_url'              => null,
        ]);

        TransactionAuditLog::create([
            'transaction_uuid' => $transaction->uuid,
            'performed_by'     => auth()->id(),
            'action'           => 'refund_initiated',
            'old_value'        => null,
            'new_value'        => $refund->uuid,
            'reason'           => $request->reason,
            'metadata'         => ['ip' => request()->ip(), 'refund_amount' => $refund->amount],
        ]);

        return ['refund_transaction' => $refund];
    }

    // -------------------------------------------------------------------------
    // RESEND NOTIFICATION
    // -------------------------------------------------------------------------

    public function resendNotification(Request $request, Transaction $transaction)
    {
        $user = $transaction->user;
        $order_detail_url = Functions::get_order_detail_page_url($transaction->order->uuid);

        match ($transaction->status) {
            TransactionStatus::SUCCESS->value => $user->notify(new PaymentSuccess($transaction, $transaction->order, $order_detail_url)),
            TransactionStatus::FAILED->value  => $user->notify(new PaymentFailed($transaction, $transaction->order, $order_detail_url)),
            default                           => null,
        };

        TransactionAuditLog::create([
            'transaction_uuid' => $transaction->uuid,
            'performed_by'     => auth()->id(),
            'action'           => 'notification_resent',
            'metadata'         => ['ip' => request()->ip(), 'status' => $transaction->status],
        ]);

        return ['message' => 'Notification sent successfully.'];
    }

    // -------------------------------------------------------------------------
    // BULK REVIEW  (mark multiple transactions as "reviewed" by the current admin)
    // -------------------------------------------------------------------------

    public function bulkReview(TransactionBulkReviewRequest $request)
    {
        $uuids = $request->transaction_uuids;

        Transaction::whereIn('uuid', $uuids)
            ->whereNull('reviewed_at')
            ->update([
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

        $logs = collect($uuids)->map(fn($uuid) => [
            'transaction_uuid' => $uuid,
            'performed_by'     => auth()->id(),
            'action'           => 'reviewed',
            'metadata'         => json_encode(['ip' => request()->ip()]),
            'created_at'       => now(),
        ])->toArray();

        TransactionAuditLog::insert($logs);

        return ['message' => count($uuids) . ' transactions marked as reviewed.'];
    }

    // -------------------------------------------------------------------------
    // EXPORT  (filtered CSV download)
    // -------------------------------------------------------------------------

    public function export(TransactionBulkExportRequest $request): StreamedResponse
    {
        $transactions = Transaction::applyFilters()
            ->customerFilterable()
            ->with(['user', 'order'])
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transactions_' . now()->format('Y-m-d_His') . '.csv"',
        ];

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // CSV header row
            fputcsv($handle, [
                'ID',
                'UUID',
                'Type',
                'Order UUID',
                'User Name',
                'User Email',
                'Amount',
                'Method',
                'Status',
                'Payment Reference',
                'Reviewed At',
                'Dispute Status',
                'Created At',
                'Deleted At',
            ]);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->id,
                    $t->uuid,
                    $t->type,
                    $t->order_uuid,
                    $t->user?->name,
                    $t->user?->email,
                    $t->amount,
                    $t->method,
                    $t->status,
                    $t->payment_reference,
                    $t->reviewed_at,
                    $t->dispute_status,
                    $t->created_at,
                    $t->deleted_at,
                ]);
            }

            fclose($handle);
        }, 'transactions.csv', $headers);
    }

    // -------------------------------------------------------------------------
    // WEBHOOK LOGS  (view raw gateway callbacks for a transaction)
    // -------------------------------------------------------------------------

    public function webhookLogs(Request $request, Transaction $transaction)
    {
        $logs = $transaction->webhookLogs()
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ['webhook_logs' => $logs];
    }

    // -------------------------------------------------------------------------
    // AUDIT TRAIL  (full history of manual changes for a transaction)
    // -------------------------------------------------------------------------

    public function auditLogs(Request $request, Transaction $transaction)
    {
        $logs = $transaction->auditLogs()
            ->with('performedBy')
            ->latest('created_at')
            ->paginate($request->integer('per_page', 20));

        return ['audit_logs' => $logs];
    }

    // -------------------------------------------------------------------------
    // DISPUTE MANAGEMENT
    // -------------------------------------------------------------------------

    public function openDispute(Request $request, Transaction $transaction)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        $transaction->update([
            'dispute_status'    => 'OPEN',
            'dispute_opened_at' => now(),
        ]);

        TransactionAuditLog::create([
            'transaction_uuid' => $transaction->uuid,
            'performed_by'     => auth()->id(),
            'action'           => 'dispute_opened',
            'reason'           => $request->reason,
            'metadata'         => ['ip' => request()->ip()],
        ]);

        return ['transaction' => $transaction->fresh()];
    }

    public function resolveDispute(Request $request, Transaction $transaction)
    {
        $request->validate([
            'outcome' => 'required|in:RESOLVED,LOST',
            'reason'  => 'required|string|max:1000',
        ]);

        $transaction->update([
            'dispute_status'      => $request->outcome,
            'dispute_resolved_at' => now(),
        ]);

        TransactionAuditLog::create([
            'transaction_uuid' => $transaction->uuid,
            'performed_by'     => auth()->id(),
            'action'           => 'dispute_resolved',
            'old_value'        => 'OPEN',
            'new_value'        => $request->outcome,
            'reason'           => $request->reason,
            'metadata'         => ['ip' => request()->ip()],
        ]);

        return ['transaction' => $transaction->fresh()];
    }
}
