<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionBulkReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum')->roleIsAdmin();
    }

    public function rules(): array
    {
        return [
            'transaction_uuids'   => ['required', 'array', 'min:1', 'max:100'],
            'transaction_uuids.*' => ['required', 'string', 'exists:transactions,uuid'],
        ];
    }
}
