<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantCreateRequest extends FormRequest
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
            'product_id' => ['required', 'exists:products,id'],
            'sku' => [
                'required',
                'min:2',
                Rule::unique('variants')
                    ->where(fn($query) => $query->where('product_id', $this->input('product_id')))
            ],
            'price' => ['required', 'numeric', 'max:999999999'],
            'special_price' => ['nullable', 'numeric', 'max:999999999'],
            'stock' => ['required', 'numeric', 'max:999999999'],
            'image' => ['nullable', 'image'],
            'variant_option_ids' => ['nullable', 'array'],
            'variant_option_ids[*]' => ['numeric', 'exists:variant_options'],
            'promotion_id' => ['nullable', 'exists:promotions,id']
        ];
    }
}
