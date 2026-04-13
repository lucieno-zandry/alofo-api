<?php
// app/Http/Requests/SettingRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by policy in controller
        return true;
    }

    public function rules(): array
    {
        $types = ['string', 'integer', 'float', 'boolean', 'json'];

        return [
            'key'         => ['required', 'string', 'max:255', Rule::unique('settings')->ignore($this->setting?->key, 'key')],
            'value'       => ['required'],
            'type'        => ['required', 'string', Rule::in($types)],
            'group'       => ['nullable', 'string', 'max:255'],
            'label'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_public'   => ['boolean'],
        ];
    }

    /**
     * Additional validation to ensure the value matches the declared type.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $value = $this->input('value');

            $valid = match ($type) {
                'string'  => is_string($value) || is_numeric($value) || is_bool($value),
                'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
                'float'   => is_numeric($value),
                'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
                'json'    => is_array($value) || is_object($value),
                default   => false,
            };

            if (! $valid) {
                $validator->errors()->add('value', "The value does not match the selected type ({$type}).");
            }
        });
    }
}