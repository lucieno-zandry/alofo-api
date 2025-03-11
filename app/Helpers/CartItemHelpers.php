<?php


namespace App\Helpers;

use App\Models\CartItem;
use App\Models\Promotion;
use App\Models\Variant;

class CartItemHelpers
{
    public static function make_item(CartItem $cart_item, array $data, ?Promotion $promotion = null): CartItem
    {
        foreach ($data as $key => $value) {
            $cart_item->$key = $value;
        }

        $cart_item->promotion_discount_applied = 0;

        $variant = Variant::find($data['variant_id']);
        $unit_price = $variant->get_price();

        $cart_item->total = $unit_price * $data['count'];

        if ($promotion) {
            $cart_item->total = DiscountHelpers::apply_discount($promotion, $cart_item->total);
            $cart_item->promotion_discount_applied = $promotion->applied_discount;
        }

        return $cart_item;
    }
}