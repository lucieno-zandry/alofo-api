<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardKpiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to access dashboard KPIs.
     */
    public function authorize(): bool
    {
        $user = $this->user('sanctum');
        return $user && $user->role === 'admin'; // or use a gate/permission
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return []; // no input validation needed for this endpoint
    }
}
