<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponCreateRequest extends FormRequest
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
            'code' => ['required', 'unique:coupons', 'size:6'],
            'type' => ['required', Rule::enum(DiscountType::class)],
            'discount' => ['required', 'numeric'],
            'min_order_value' => ['required', 'numeric'],
            'max_uses' => ['required', 'numeric'],
            'uses_count' => ['nullable', 'numeric'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'is_active' => ['nullable', 'boolean']
        ];
    }
}
