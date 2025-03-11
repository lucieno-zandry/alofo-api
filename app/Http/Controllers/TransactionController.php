<?php

namespace App\Http\Controllers;

use App\Events\Payment;
use App\Helpers\Functions;
use App\Http\Requests\TransactionCreateRequest;
use App\Http\Requests\TransactionDeleteRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function store(TransactionCreateRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $transaction = Transaction::create($data);

        Payment::dispatchIf($transaction->status === Transaction::$STATUS_SUCCESS, $transaction->order);

        return [
            'transaction' => $transaction
        ];
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction)
    {
        $data = $request->validated();

        $transaction->update($data);

        Payment::dispatchIf($transaction->status === Transaction::$STATUS_SUCCESS, $transaction->order);

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
