<?php

namespace App\Http\Requests;

use App\Rules\InStock;
use App\Rules\UsableCoupon;
use Illuminate\Foundation\Http\FormRequest;

class OrderCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('sanctum')->roleIsCustomer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cart_items_ids' => ['array'],
            'cart_items_ids.*' => ['exists:cart_items,id'],
            'variants' =>  ['array'],
            'variants.*' => ['array'],
            'variants.*.variant_id' => ['numeric'],
            'variants.*.count' => ['numeric', new InStock()],
            'coupon_code' => ['nullable', new UsableCoupon()],
        ];
    }
}
