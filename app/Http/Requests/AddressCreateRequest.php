<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => ['required', 'min:2', 'max:255'],
            'line1' => ['required', 'min:2', 'max:255'],
            'line2' => ['nullable', 'min:2', 'max:255'],
            'line3' => ['nullable', 'min:2', 'max:255'],
            'phone_number' => ['required', 'min:10', 'max:20', 'regex:/^(\+)*[\d\s]+$/'],
            'user_id' => ['required', 'numeric'],
            'is_default' => ['nullable', 'boolean']
        ];
    }

    public function prepareForValidation()
    {
        return $this->merge(['user_id' => $this->user()->id]);
    }
}
