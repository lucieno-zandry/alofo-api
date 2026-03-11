<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page'          => 'nullable|integer|min:1',
            'per_page'      => 'nullable|integer|min:1|max:200',
            'status'        => 'nullable|string',
            'method'        => 'nullable|string',
            'type'          => 'nullable|string',
            'date_from'     => 'nullable|date',
            'date_to'       => 'nullable|date',
            'amount_min'    => 'nullable',
            'amount_max'    => 'nullable',
            'search'        => 'nullable|string',
            'order_uuid'    => 'nullable|string',
            'dispute_status' => 'nullable|string',
            'sort_by'       => 'nullable|string',
            'sort_dir'      => 'nullable|in:asc,desc',
            'reviewed'      => 'nullable|in:yes,no',
        ];
    }
}
