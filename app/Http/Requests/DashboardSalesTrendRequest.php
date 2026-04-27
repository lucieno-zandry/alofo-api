<?php
// app/Http/Requests/Dashboard/DashboardSalesTrendRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardSalesTrendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('sanctum') && $this->user('sanctum')->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'days' => 'sometimes|integer|min:1|max:90', // allow custom range? optional
        ];
    }
}