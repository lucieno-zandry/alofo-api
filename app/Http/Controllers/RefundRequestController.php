<?php

// app/Http/Controllers/RefundRequestController.php (new controller)
namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Http\Requests\RefundRequestApproveRequest;
use App\Http\Requests\RefundRequestRejectRequest;
use App\Notifications\RefundApproved;
use App\Notifications\RefundRejected;
use App\Services\TransactionRefundService;
use Illuminate\Http\Request;

class RefundRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = RefundRequest::with(['user', 'transaction'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(20);

        return ['refund_requests' => $requests];
    }

    public function approve(RefundRequestApproveRequest $request, RefundRequest $refundRequest)
    {
        if ($refundRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed.'], 422);
        }

        // Call the existing refund method on the transaction
        $transaction = $refundRequest->transaction;
        // We need to reuse the refund logic. Instead of duplicating, we can call a service or the existing method.
        // For simplicity, assume we call a service:
        $refundTransaction = app(TransactionRefundService::class)->refund(
            transaction: $transaction,
            amount: $refundRequest->amount,
            reason: $refundRequest->reason,
            performedBy: auth()->id()
        );

        $refundRequest->update([
            'status'       => 'approved',
            'admin_notes'  => $request->admin_notes,
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
        ]);

        // Notify customer
        $transaction->user->notify(new RefundApproved($refundRequest, $refundTransaction));

        return ['refund_request' => $refundRequest->fresh()];
    }

    public function reject(RefundRequestRejectRequest $request, RefundRequest $refundRequest)
    {
        if ($refundRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed.'], 422);
        }

        $refundRequest->update([
            'status'       => 'rejected',
            'admin_notes'  => $request->admin_notes,
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
        ]);

        // Notify the customer – use the relationship to get the user
        $refundRequest->transaction->user->notify(new RefundRejected($refundRequest));

        return ['refund_request' => $refundRequest->fresh()];
    }
}
