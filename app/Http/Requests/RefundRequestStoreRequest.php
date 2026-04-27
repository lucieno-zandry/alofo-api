<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class RefundRequestStoreRequest extends FormRequest
{
    public function authorize()
    {
        $transaction = $this->route('transaction');
        // Must own the transaction and it must be in SUCCESS status
        return $this->user('sanctum')->can('requestRefund', $transaction);
    }

    public function rules()
    {
        $transaction = $this->route('transaction');
        return [
            'amount' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:' . ($transaction?->amount ?? 0),
            ],
            'reason' => 'required|string|min:10|max:1000',
        ];
    }
}
