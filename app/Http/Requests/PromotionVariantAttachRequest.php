<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;

class PromotionVariantAttachRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('attachVariant', Promotion::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'variant_id' => 'required|exists:variants,id'
        ];
    }
}
