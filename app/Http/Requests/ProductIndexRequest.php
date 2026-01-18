<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 🔍 Search
            'search' => ['nullable', 'string', 'max:100'],

            // 📦 Category
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],

            // 💰 Price filters
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price'],

            // 🎛 Variant options (multi-select)
            'variant_option_ids' => ['nullable', 'array'],
            'variant_option_ids.*' => ['integer', 'exists:variant_options,id'],

            // 📄 Pagination (offset-based)
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],

            // 📊 Sorting
            'order_by' => ['nullable', 'in:created_at,title'],
            'direction' => ['nullable', 'in:ASC,DESC'],

            // 🔗 Eager loading
            'with' => ['nullable', 'string'], // validated further below
        ];
    }

    /**
     * Normalize inputs before controller / query layer
     */
    protected function prepareForValidation(): void
    {
        // Normalize direction
        if ($this->has('direction')) {
            $this->merge([
                'direction' => strtoupper($this->direction),
            ]);
        }

        // Normalize comma-separated arrays
        if ($this->has('variant_option_ids') && is_string($this->variant_option_ids)) {
            $this->merge([
                'variant_option_ids' => explode(',', $this->variant_option_ids),
            ]);
        }

        // Default pagination
        $this->merge([
            'limit' => $this->limit ?? 20,
            'offset' => $this->offset ?? 0,
        ]);
    }

    /**
     * Whitelist allowed relations
     */
    public function relations(): array
    {
        if (!$this->filled('with')) {
            return [];
        }

        $allowed = [
            'category',
            'variants',
            'variants.variant_options',
            'images',
            'variant_groups',
            'variant_groups.variant_options',
        ];

        return array_values(
            array_intersect(
                explode(',', $this->with),
                $allowed
            )
        );
    }

    /**
     * Safe sorting accessors
     */
    public function orderBy(): string
    {
        return $this->get('order_by', 'created_at');
    }

    public function direction(): string
    {
        return $this->get('direction', 'DESC');
    }
}
