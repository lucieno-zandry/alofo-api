<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->roleIsAdmin();
    }

    public function rules(): array
    {
        return [
            'block_type' => 'sometimes|string|max:50|in:hero,collection_grid,featured_products,story,comparison,testimonials,faq,cta_banner,trust_bar',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string',
            'content' => 'nullable|array',
            'landing_able_type' => 'nullable|string|max:50',
            'landing_able_id' => 'nullable|integer|required_with:landing_able_type',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function prepareForValidation()
    {
        $content = $this->get('content');

        if ($content && is_string($content)) {
            $this->merge([
                'content' => json_decode($content, true),
            ]);
        }
    }
}
