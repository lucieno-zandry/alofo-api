<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Enums\PaymentMethod;          // New enum: 'card', 'paypal', ...
use App\Enums\CardBrand;              // New enum: 'visa', 'mastercard', ...
use App\Rules\NoSuccessfulPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [Rule::enum(TransactionStatus::class), 'not_in:SUCCESS'],
            'informations' => ['nullable', 'array'],
            'order_uuid' => ['required', 'exists:orders,uuid', new NoSuccessfulPayment],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'card_brand' => [
                'nullable',
                'required_if:payment_method,card',   // only required when payment_method is 'card'
                Rule::enum(CardBrand::class),
            ],
            'payment_method_label' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
