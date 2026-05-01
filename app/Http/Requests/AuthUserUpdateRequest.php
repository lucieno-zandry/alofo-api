<?php

namespace App\Http\Requests;

use App\Helpers\Functions;
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
        return !!$this->user('sanctum');
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
            'avatar_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'client_code_id' => ['nullable', 'numeric', $this->clientCodeRule],
        ];

        if (!$this->user('sanctum')->roleIsGuest())
            if ($this->has('email') || $this->has('password')) {
                $rules['current_password'] = ['required', 'current_password'];
            }

        return $rules;
    }

    public function prepareForValidation()
    {
        if (!$this->user('sanctum')->roleIsGuest() || !$this->input('email')) return;

        $name = Functions::get_email_username($this->input('email'));

        if ($name)
            $this->mergeIfMissing([
                'name' => $name
            ]);
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
