<?php


namespace App\Helpers;

use App\Models\CartItem;
use App\Models\Promotion;
use App\Models\Variant;

use function Illuminate\Log\log;

class CartItemHelpers
{
    public static function make_item(CartItem $cart_item, array $data = []): CartItem
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $cart_item->$key = $value;
            }
        } else {
            $data = $cart_item->toArray();
        }

        /** @var ?Variant */
        $variant = request()->variant ?: Variant::find($cart_item->variant_id);

        /** @var ?Promotion */
        $promotion = $cart_item->promotion_id ? Promotion::find($cart_item->promotion_id) : null;

        if ($variant) {
            $cart_item->variant_snapshot = [
                'id'            => $variant->id,
                'sku'           => $variant->sku,
                'price'         => $variant->price,
                'special_price' => $variant->special_price,
                'image'         => $variant->image?->url ?? null,
            ];

            $variant->load(['product', 'variant_options.variant_group']);

            $cart_item->product_snapshot = [
                'id' => $variant->product->id,
                'title' => $variant->product->title,
                'slug' => $variant->product->slug,
                'category_id' => $variant->product->category_id,
                'main_image' => $variant->product->images->first()?->url ?? null,
            ];

            $variant_options_snapshot = Functions::get_variant_options_snapshot($variant->variant_options);

            $cart_item->variant_options_snapshot = $variant_options_snapshot;
        }

        $cart_item->promotion_discount_applied = 0;

        $cart_item->unit_price = $variant->get_price();

        $cart_item->total = $cart_item->unit_price * $data['count'];

        if ($promotion) {
            $cart_item->total = DiscountHelpers::apply_discount($promotion, $cart_item->total);
            $cart_item->promotion_discount_applied = $promotion->applied_discount;
        }

        $cart_item->save();
        return $cart_item;
    }
}
