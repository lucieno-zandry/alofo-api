<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequestApproveRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->roleIsAdmin();
    }

    public function rules()
    {
        return [
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }
}
