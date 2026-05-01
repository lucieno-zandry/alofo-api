<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Helpers\Functions;
use App\Models\ClientCode;
use App\Rules\CanBeUsedClientCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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

        return [
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['min:6', 'max:32', 'confirmed'],
            'name' => ['string', 'min:2', 'max:32'],
            'role' => [Rule::enum(UserRole::class)],
            'avatar_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'client_code_id' => [$this->clientCodeRule],
            'preferred_theme' => ['sometimes', 'string', 'max:10'],
            'preferred_language' => ['sometimes', 'string', 'max:10'],
            'preferred_timezone' => ['sometimes', 'timezone'],
            'preferred_currency' => ['sometimes', 'string', 'size:3'],
        ];
    }

    public function prepareForValidation()
    {
        $name = Functions::get_email_username($this->input('email', '')) ?: '';

        $this->mergeIfMissing([
            'role' => UserRole::CLIENT->value,
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
