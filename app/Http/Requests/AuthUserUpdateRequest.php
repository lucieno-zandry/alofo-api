<?php

namespace App\Http\Requests;

use App\Models\ClientCode;
use App\Rules\CanBeUsedClientCode;
use Illuminate\Foundation\Http\FormRequest;

class AuthUserUpdateRequest extends FormRequest
{
    protected ?ClientCode $clientCode = null;
    protected ?CanBeUsedClientCode $clientCodeRule = null;

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
        $this->clientCodeRule = new CanBeUsedClientCode;

        $rules = [
            'email' => ['email', 'unique:users'],
            'password' => ['min:6', 'max:32'],
            'name' => ['min:4', 'max:32'],
            'role' => ['string'],
            'avatar_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'client_code_id' => ['nullable', 'numeric', $this->clientCodeRule],
        ];

        if ($this->has('email') || $this->has('password')) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }

    protected function passedValidation(): void
    {
        $this->clientCode = $this->clientCodeRule?->clientCode;
    }

    public function clientCode(): ?ClientCode
    {
        return $this->clientCode;
    }
}
