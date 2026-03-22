<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UserStatusStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Log::debug($this->route('user'));
        return $this->user()->can('updateStatus', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:approved,blocked,suspended',
            'reason' => 'nullable',
            'set_by' => 'required',
            'expires_at' => 'nullable|date',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->route('user')->id,
            'set_by' => $this->user()->id,
        ]);
    }
}
