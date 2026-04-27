<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;

class PromotionBulkAttachVariantsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Get the promotion from route model binding
        $promotion = $this->route('promotion');

        // Ensure promotion exists and user has permission to update it
        return $promotion && $this->user('sanctum')->can('update', $promotion);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'variant_ids' => ['required', 'array', 'min:1'],
        ];
    }

    /**
     * Custom error messages (optional).
     */
    public function messages(): array
    {
        return [
            'variant_ids.required' => 'At least one variant ID is required.',
            'variant_ids.array' => 'Variant IDs must be an array.',
            'variant_ids.min' => 'Please provide at least one variant ID.',
        ];
    }
}
