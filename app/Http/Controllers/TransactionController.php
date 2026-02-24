<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Events\FailedPayment;
use App\Events\Payment;
use App\Helpers\Functions;
use App\Http\Requests\TransactionCreateRequest;
use App\Http\Requests\TransactionDeleteRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Transaction;
use App\Jobs\FailPendingTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use function Illuminate\Log\log;

class TransactionController extends Controller
{
    public function store(TransactionCreateRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $uuid = Str::uuid()->toString();
        $data['uuid'] = $uuid;

        // À mettre à jour lors d'une réelle mise en place de méthode de paiement;

        $token = request()->header('Authorization');
        $redirect_url = "http://localhost:5173/order/{$request->order_uuid}";
        $payment_url = "http://127.0.0.1:5500/index.html?amount={$request->amount}&transaction_uuid={$uuid}&token={$token}&redirect_url={$redirect_url}";

        $data['payment_url'] = urlencode($payment_url);

        $transaction = Transaction::create($data);
        $transaction->payment_url = $payment_url;

        // Schedule a job to mark this transaction as failed if it remains pending.
        FailPendingTransaction::dispatch($transaction->uuid)->delay(now()->addMinutes(10));

        Payment::dispatchIf($transaction->status === TransactionStatus::SUCCESS->value, $transaction->order, $transaction);
        FailedPayment::dispatchIf($transaction->status === TransactionStatus::FAILED->value, $transaction->order, $transaction);

        return [
            'transaction' => $transaction
        ];
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction)
    {
        $data = $request->validated();

        $transaction->update($data);

        Payment::dispatchIf($transaction->status === TransactionStatus::SUCCESS->value, $transaction->order, $transaction);
        FailedPayment::dispatchIf($transaction->status === TransactionStatus::FAILED->value, $transaction->order, $transaction);

        return [
            'transation' => $transaction
        ];
    }

    public function destroy(TransactionDeleteRequest $request)
    {
        $transaction_ids = implode(',', $request->transaction_ids);

        $deleted = Transaction::whereIn('id', $transaction_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $transactions = Transaction::applyFilters()
            ->customerFilterable()
            ->get();

        return [
            'transactions' => $transactions
        ];
    }

    public function show(int $transaction_id)
    {
        $transaction = Transaction::withRelations()->find($transaction_id);

        return [
            'transaction' => $transaction
        ];
    }
}
