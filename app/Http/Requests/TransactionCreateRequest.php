<?php

namespace App\Http\Requests;

use App\Rules\NoSuccessfulPayment;
use Illuminate\Foundation\Http\FormRequest;

class TransactionCreateRequest extends FormRequest
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
            'status' => ['required', 'string'],
            'informations' => ['nullable'],
            'order_uuid' => ['required', 'exists:orders,uuid', new NoSuccessfulPayment]
        ];
    }
}
