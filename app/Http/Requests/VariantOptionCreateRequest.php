<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantOptionCreateRequest extends FormRequest
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
            'value' => [
                'required',
                'min:2',
                Rule::unique('variant_options')
                    ->where(
                        'variant_group_id',
                        $this->input('variant_group_id')
                    )
            ],
            'variant_group_id' => ['required', 'exists:variant_groups,id'],
            'variant_ids' => ['nullable', 'array'],
            'variant_ids[*]' => ['numeric', 'exists:variants,id']
        ];
    }
}
