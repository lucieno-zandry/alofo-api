<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Rules\CanBeUsedClientCode;
use Illuminate\Validation\Rules\File;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
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
        $rules = [
            'email' => ['email', 'unique:users'],
            'password' => ['min:6', 'max:32'],
            'name' => ['min:4', 'max:32'],
            'role' => [Rule::enum(UserRole::class)],
            'image' => ['nullable', 'image'],
            'client_code_id' => ['nullable', 'numeric', new CanBeUsedClientCode],
            'approved_at' => ['nullable', 'date']
        ];

        return $rules;
    }
}
