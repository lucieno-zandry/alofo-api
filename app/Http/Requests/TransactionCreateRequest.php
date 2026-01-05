<?php

namespace App\Http\Requests;

use App\Enums\TransactionMethod;
use App\Enums\TransactionStatus;
use App\Rules\NoSuccessfulPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'status' => [Rule::enum(TransactionStatus::class), 'not_in:SUCCESS'],
            'informations' => ['nullable'],
            'order_uuid' => ['required', 'exists:orders,uuid', new NoSuccessfulPayment],
            'method' => ['required', Rule::enum(TransactionMethod::class)],
            'amount' => ['required', 'numeric', 'min:0']
        ];
    }
}
