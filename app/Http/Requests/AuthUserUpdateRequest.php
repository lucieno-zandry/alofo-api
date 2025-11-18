<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthUserUpdateRequest extends FormRequest
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
        $rules = [
            'email' => ['email', 'unique:users'],
            'password' => ['min:6', 'max:32'],
            'name' => ['min:4', 'max:32'],
            'role' => ['string'],
            'image' => ['nullable', 'image'],
            'client_code_id' => ['nullable', 'alpha_num:ascii', 'min:6', 'max:6']
        ];

        if ($this->has('email') || $this->has('password')) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }
}
