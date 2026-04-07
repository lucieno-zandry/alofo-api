<?php

namespace App\Services;

use App\Models\CartItem;

class CartItemShippingTransformer
{
    public static function toShippingItems($cartItems): array
    {
        $items = [];
        foreach ($cartItems as $item) {
            $variantSnapshot = $item->variant_snapshot;
            $weight = $variantSnapshot['weight_kg'] ?? 0;
            $price = $variantSnapshot['price'] ?? 0;

            $items[] = [
                'weight_kg' => $weight,
                'quantity' => $item->count,
                'price' => $price,
            ];
        }
        return $items;
    }
}
