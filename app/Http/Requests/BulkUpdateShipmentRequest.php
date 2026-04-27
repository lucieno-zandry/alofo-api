<?php

namespace App\Http\Requests;

use App\Enums\ShipmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Check if user is admin or manager
        return $this->user('sanctum') && in_array($this->user('sanctum')->role, ['admin', 'manager']);
    }

    public function rules(): array
    {
        return [
            'order_uuids'            => ['required', 'array', 'min:1'],
            'order_uuids.*'          => ['required', 'string', 'exists:orders,uuid'],
            'status'                 => ['required', 'string', Rule::in(array_column(ShipmentStatus::cases(), 'value'))],
            'data'                   => ['sometimes', 'array'],
            'data.carrier'           => ['sometimes', 'string', 'max:255'],
            'data.tracking_number'   => ['sometimes', 'string', 'max:255'],
            'data.estimated_delivery' => ['sometimes', 'date'],
            'data.shipped_date'      => ['sometimes', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_uuids.required' => 'Please select at least one order.',
            'order_uuids.*.exists' => 'One or more orders do not exist.',
            'status.in'            => 'Invalid shipment status.',
        ];
    }
}
