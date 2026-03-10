<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gateway callbacks should never update an already-succeeded transaction
        return $this->route('transaction')?->status !== TransactionStatus::SUCCESS->value;
    }

    public function rules(): array
    {
        return [
            'status'       => ['sometimes', Rule::enum(TransactionStatus::class)],
            'informations' => ['nullable', 'array'],
        ];
    }
}
