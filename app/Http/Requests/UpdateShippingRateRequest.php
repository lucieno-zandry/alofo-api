<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->roleIsAdmin();
    }

    public function rules(): array
    {
        return [
            'country_code' => [
                'sometimes',
                'string',
                'regex:/^(\*|[A-Z]{2})$/i',
            ],
            'city_pattern' => 'nullable|string|max:255',
            'min_weight_kg' => 'nullable|numeric|min:0',
            'max_weight_kg' => 'nullable|numeric|min:0|gt:min_weight_kg',
            'rate' => 'sometimes|numeric|min:0',
            'rate_per_kg' => 'nullable|numeric|min:0',
        ];
    }
}
