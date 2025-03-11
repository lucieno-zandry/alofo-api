<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use App\Models\Variant;
use App\Rules\BelongsToOne;
use App\Rules\InStock;
use App\Rules\IsActive;
use Illuminate\Foundation\Http\FormRequest;

class CartItemCreateRequest extends FormRequest
{
    public ?Variant $variant = null;
    public ?Promotion $promotion = null;
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
        return [
            'variant_id' => ['required', 'exists:variants,id'],
            'count' => ['required', 'numeric', 'min:1', new InStock($this->variant)],
            'promotion_id' => [
                'nullable',
                'exists:promotions,id',
                new BelongsToOne(
                    $this->promotion,
                    'variants',
                    request('variant_id')
                ),
                new IsActive($this->promotion),
            ]
        ];
    }

    public function prepareForValidation()
    {
        if ($this->variant_id) {
            $this->variant = Variant::where('id', $this->variant_id)->first();
        }

        if ($this->promotion_id) {
            $this->promotion = Promotion::where('id', $this->promotion_id)->first();
        }
    }
}
