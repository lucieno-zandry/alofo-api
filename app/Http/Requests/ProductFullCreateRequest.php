<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductFullCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            // product
            'title' => ['required', 'min:2'],
            'slug' => ['required', 'unique:products,slug'],
            'description' => ['nullable'],
            'category_id' => ['nullable', 'exists:categories,id'],

            // images
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'max:4096'],

            // groups
            'variant_groups' => ['nullable', 'array'],
            'variant_groups.*.name' => ['required', 'min:2'],
            'variant_groups.*.options' => ['nullable', 'array'],
            'variant_groups.*.options.*.value' => ['required', 'min:1'],

            // variants
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'min:2'],
            'variants.*.price' => ['required', 'numeric'],
            'variants.*.special_price' => ['nullable', 'numeric'],
            'variants.*.stock' => ['required', 'integer'],

            // option references
            'variants.*.option_refs' => ['nullable', 'array'],
            'variants.*.option_refs.*' => ['string'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->slug) {
            $slug =  $this->slug . "-" . uuid_create();
            $this->merge(["slug" => $slug]);
        }
    }
}
