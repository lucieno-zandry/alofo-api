<?php
// app/Http/Requests/Variant/VariantIndexRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VariantIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') && $this->user('sanctum')->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'page'        => 'sometimes|integer|min:1',
            'per_page'    => 'sometimes|integer|min:1|max:100',
            'sort_by'     => 'sometimes|string|in:id,sku,price,stock,created_at,updated_at',
            'sort_order'  => 'sometimes|string|in:asc,desc',
            'product_id'  => 'sometimes|integer|exists:products,id',
            'sku'         => 'sometimes|string|max:255',
            'min_price'   => 'sometimes|numeric|min:0',
            'max_price'   => 'sometimes|numeric|min:0',
            'min_stock'   => 'sometimes|integer|min:0',
            'max_stock'   => 'sometimes|integer|min:0',
            'low_stock'   => 'sometimes|boolean',
            'search'      => 'sometimes|string|max:255',
            'with'        => 'sometimes|array',
            'with.*'      => 'string|in:product,image,variant_options,variant_options.variant_group,promotions',
        ];
    }
}