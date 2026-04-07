<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function Illuminate\Log\log;

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
            'variants.*.stock'          => ['required', 'integer'],
            'variants.*.option_refs'    => ['nullable', 'array'],
            'variants.*.option_refs.*'  => ['string'],
            'variants.*.image' => ['nullable', 'image'],
            'variants.*.weight_kg' => ['nullable', 'numeric'],
            'variants.*.length_cm' => ['nullable', 'numeric'],
            'variants.*.width_cm' => ['nullable', 'numeric'],
            'variants.*.height_cm' => ['nullable', 'numeric'],
        ];
    }

    public function prepareForValidation()
    {
        $data = $this->all();
        $merge = [];

        if (isset($data['variants']) && is_array($data['variants'])) {
            foreach ($data['variants'] as &$variant) {
                if (array_key_exists('image', $variant) && $variant['image'] === '') {
                    $variant['image'] = null;
                }
            }
            $merge['variants'] = $data['variants'];
        }

        if ($this->slug) {
            $slug = uuid_create() . '-' . $this->slug;
            $merge['slug'] = $slug;
        }

        $this->merge($merge);
    }
}
