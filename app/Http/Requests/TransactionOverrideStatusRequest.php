<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionOverrideStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can manually override a transaction status
        return $this->user()->roleIsAdmin();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TransactionStatus::class)],
            // reason is mandatory for manual overrides — creates a paper trail
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required when manually overriding a transaction status.',
            'reason.min'      => 'Please provide a more descriptive reason (at least 10 characters).',
        ];
    }
}
