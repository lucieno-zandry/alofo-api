<?php

namespace App\Helpers;

use App\Models\CartItem;
use App\Models\Variant;

class CartItemHelpers
{
    /**
     * Create or update a cart item with current prices and snapshots.
     *
     * @param CartItem $cartItem
     * @param array $data
     * @param Variant|null $variant Pre‑loaded variant to avoid extra query
     * @return CartItem
     */
    public static function make_item(CartItem $cartItem, array $data = [], ?Variant $variant = null): CartItem
    {
        // Fill basic attributes
        foreach ($data as $key => $value) {
            $cartItem->$key = $value;
        }

        // Load variant with necessary relations if not provided
        if (!$variant) {
            $variant = Variant::with([
                'promotions',
                'product.images',
                'variant_options.variant_group',
                'image'
            ])->find($cartItem->variant_id);
        }

        if (!$variant) {
            throw new \Exception("Variant not found for cart item.");
        }

        // Compute effective price for the current user
        $effectivePrice = $variant->effective_price; // uses auth()->user()
        $basePrice = $variant->price;

        // Get applied promotions (already filtered for user)
        $appliedPromotions = $variant->applied_promotions; // returns Collection of Promotion models

        // Build snapshot of applied promotions for frontend badges
        $promotionsSnapshot = $appliedPromotions->map(fn($promo) => [
            'id'       => $promo->id,
            'name'     => $promo->name,          // assuming a name field exists
            'badge'    => $promo->badge,         // optional badge text/identifier
            'discount' => $promo->discount,
            'type'     => $promo->type,
        ])->values()->toArray();

        // --- Snapshots ---
        // Variant snapshot (base price only, effective price is stored separately)
        $cartItem->variant_snapshot = [
            'id'    => $variant->id,
            'sku'   => $variant->sku,
            'price' => $variant->price,
            'image' => $variant->image?->url ?? null,
        ];

        // Product snapshot
        $cartItem->product_snapshot = [
            'id'          => $variant->product->id,
            'title'       => $variant->product->title,
            'slug'        => $variant->product->slug,
            'category_id' => $variant->product->category_id,
            'main_image'  => $variant->product->images->first()?->url ?? null,
        ];

        // Variant options snapshot
        $cartItem->variant_options_snapshot = Functions::get_variant_options_snapshot($variant->variant_options);

        // --- Pricing ---
        $cartItem->unit_price = $effectivePrice;
        $cartItem->promotion_discount_applied = $basePrice - $effectivePrice; // total discount
        $cartItem->total = $effectivePrice * $data['count'];

        // Store applied promotions snapshot (new JSON column)
        $cartItem->applied_promotions_snapshot = $promotionsSnapshot;

        $cartItem->save();

        return $cartItem;
    }
}
