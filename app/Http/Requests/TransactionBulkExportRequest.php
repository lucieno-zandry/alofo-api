<?php
// app/Http/Requests/TransactionBulkExportRequest.php

namespace App\Http\Requests;

use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionBulkExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Exports are restricted to admins and managers
        return in_array($this->user()->role, ['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'status'         => ['nullable', Rule::enum(TransactionStatus::class)],
            'method'         => ['nullable', Rule::enum(TransactionMethod::class)],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'amount_min'     => ['nullable', 'numeric', 'min:0'],
            'amount_max'     => ['nullable', 'numeric', 'gte:amount_min'],
            'search'         => ['nullable', 'string', 'max:255'],
            'order_uuid'     => ['nullable', 'string'],
            'dispute_status' => ['nullable', 'in:OPEN,RESOLVED,LOST'],
            'type'           => ['nullable', 'in:PAYMENT,REFUND,MANUAL'],
        ];
    }
}
