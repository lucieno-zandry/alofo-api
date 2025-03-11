<?php

namespace App\Http\Requests;

use App\Rules\UsableCoupon;
use Illuminate\Foundation\Http\FormRequest;

class OrderCreateRequest extends FormRequest
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
            'address_id' => ['required', 'exists:addresses,id'],
            'cart_item_ids' => ['required', 'array'],
            'cart_item_ids[*]' => ['numeric'],
            'coupon_id' => ['nullable', 'exists:coupons,id', new UsableCoupon]
        ];
    }
}
