<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductFullUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            // Product basics
            'title'       => ['required', 'min:2'],
            'slug'        => ['required', Rule::unique('products', 'slug')->ignore($product->id)],
            'description' => ['nullable'],
            'category_id' => ['nullable', 'exists:categories,id'],

            // Images: IDs of existing images to KEEP (others will be deleted)
            'existing_image_ids'   => ['nullable', 'array'],
            'existing_image_ids.*' => ['integer', 'exists:images,id'],

            // New image uploads
            'images'   => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],

            // Variant groups
            'variant_groups'              => ['nullable', 'array'],
            'variant_groups.*.id'         => ['nullable', 'integer', 'exists:variant_groups,id'],
            'variant_groups.*.name'       => ['required', 'min:2'],
            'variant_groups.*.options'    => ['nullable', 'array'],
            'variant_groups.*.options.*.id'    => ['nullable', 'integer', 'exists:variant_options,id'],
            'variant_groups.*.options.*.value' => ['required', 'min:1'],

            // Variants
            'variants'                  => ['nullable', 'array'],
            'variants.*.id'             => ['nullable', 'integer', 'exists:variants,id'],
            'variants.*.sku'            => ['required', 'min:2'],
            'variants.*.price'          => ['required', 'numeric'],
            'variants.*.special_price'  => ['nullable', 'numeric'],
            'variants.*.stock'          => ['required', 'integer'],
            'variants.*.option_refs'    => ['nullable', 'array'],
            'variants.*.option_refs.*'  => ['string'],
        ];
    }
}
