<?php

namespace App\Http\Requests;

use App\Models\Promotion;
use App\Models\Variant;
use App\Rules\BelongsToOne;
use App\Rules\InStock;
use App\Rules\IsActive;
use Illuminate\Foundation\Http\FormRequest;

class CartItemUpdateRequest extends FormRequest
{

    public ?Variant $variant = null;
    public ?Promotion $promotion = null;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->cart_item->is_not_ordered();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'count' => ['numeric', 'min:1', new InStock($this->variant)],
            'promotion_id' => [
                'nullable',
                'exists:promotions,id',
                new BelongsToOne(
                    $this->promotion,
                    'variants',
                    $this->variant?->id
                ),
                new IsActive($this->promotion),
            ]
        ];
    }

    public function prepareForValidation()
    {
        if (!$this->cart_item)
            return;

        if ($this->cart_item->variant_id) {
            $this->variant = Variant::where('id', $this->cart_item->variant_id)->first();
        }

        $promotion_id = null;

        if ($this->has('promotion_id')) {
            if ($this->promotion_id) {
                $promotion_id = $this->promotion_id;
            }
        } else {
            $promotion_id = $this->cart_item->promotion_id;
        }

        if ($promotion_id) {
            $this->promotion = Promotion::where('id', $promotion_id)->first();
        }
    }
}
