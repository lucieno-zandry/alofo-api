<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum')?->roleIsAdmin(); // adjust as needed
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'carrier' => 'required|in:custom,fedex,colissimo',
            'is_active' => 'boolean',
            'calculation_type' => 'required|in:flat_rate,weight_based,api',
            'flat_rate' => 'required_if:calculation_type,flat_rate|nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'rate_per_kg' => 'required_if:calculation_type,weight_based|nullable|numeric|min:0',
            'api_config' => 'nullable|array',
            'min_delivery_days' => 'nullable|integer|min:1',
            'max_delivery_days' => 'nullable|integer|min:1|gte:min_delivery_days',
            'allowed_countries' => 'nullable|array',
            'allowed_countries.*' => 'string|size:2',
        ];
    }
}
