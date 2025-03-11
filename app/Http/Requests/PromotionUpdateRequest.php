<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionUpdateRequest extends FormRequest
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
            'discount' => ['numeric', 'max:9999'],
            'type' => [Rule::enum(DiscountType::class)],
            'start_date' => ['date'],
            'end_date' => ['date'],
            'is_active' => ['boolean'],
            'variant_ids' => ['nullable', 'array'],
            'variant_ids[*]' => ['numeric', 'exists:variants,id']
        ];
    }
}
