<?php
// app/Http/Requests/TransactionRefundRequest.php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;

class TransactionRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can initiate refunds
        // The transaction must be in SUCCESS status to be refundable
        return $this->user()->roleIsAdmin()
            && $this->route('transaction')?->status === TransactionStatus::SUCCESS->value;
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');

        return [
            // Partial refunds supported — must not exceed original amount
            'amount' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:' . ($transaction?->amount ?? PHP_INT_MAX),
            ],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
