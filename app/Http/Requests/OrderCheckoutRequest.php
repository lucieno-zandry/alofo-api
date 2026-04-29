<?php

namespace App\Http\Requests;

use App\Models\Variant;
use App\Rules\InStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Override;

class OrderCheckoutRequest extends FormRequest
{
    protected array $variantModels = [];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('sanctum')->roleIsCustomer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cart_items_ids' => ['array'],
            'cart_items_ids.*' => ['exists:cart_items,id'],
            'variants' =>  ['array'],
            'variants.*' => ['array'],
            'variants.*.variant_id' => ['numeric'],
            'variants.*.count' => ['numeric', new InStock($this->variantModels)],
        ];
    }

    public function prepareForValidation()
    {
        $ids = collect($this->input('variants', []))
            ->pluck('variant_id')
            ->filter()
            ->unique();

        $this->variantModels = Variant::whereIn('id', $ids)
            ->get()
            ->keyBy('id')
            ->all();
    }
}
