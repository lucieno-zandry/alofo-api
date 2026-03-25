<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'          => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone'          => ['required', 'string', 'min:7', 'max:20', 'regex:/^\+?[\d\s\-\(\)]+$/'],
            'phone_alt'      => ['nullable', 'string', 'min:7', 'max:20', 'regex:/^\+?[\d\s\-\(\)]+$/'],
            'line1'          => ['required', 'string', 'min:2', 'max:255'],
            'line2'          => ['nullable', 'string', 'min:2', 'max:255'],
            'city'           => ['required', 'string', 'min:2', 'max:100'],
            'state'          => ['nullable', 'string', 'min:2', 'max:100'],
            'postal_code'    => ['required', 'string', 'min:2', 'max:20'],
            'country'        => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'], // ISO alpha-2
            'address_type'   => ['nullable', 'in:billing,shipping,both'],
            'is_default'     => ['nullable', 'boolean'],
            'user_id'        => ['required', 'numeric'], // auto-filled in prepareForValidation
        ];
    }

    public function prepareForValidation()
    {
        $this->merge(['user_id' => $this->user()->id]);
    }
}
