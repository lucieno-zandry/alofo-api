<?php
// app/Http/Requests/RefundRequestRejectRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequestRejectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can reject refund requests
        return $this->user('sanctum') && $this->user('sanctum')->roleIsAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }
}
