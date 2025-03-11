<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->roleIsAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['unique:coupons', 'size:6'],
            'type' => [Rule::enum(DiscountType::class)],
            'discount' => ['numeric'],
            'min_order_value' => ['numeric'],
            'max_uses' => ['numeric'],
            'uses_count' => ['nullable', 'numeric'],
            'start_date' => ['date'],
            'end_date' => ['date'],
            'is_active' => ['nullable', 'boolean']
        ];
    }
}
