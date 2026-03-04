<?php

namespace App\Helpers;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderHelpers
{
    /**
     * Refresh an existing order – recalculates totals using current cart items.
     * Does NOT modify the cart items themselves.
     */
    public static function refresh_order(Order $order)
    {
        $order = self::make_order($order, $order->cart_items);
        $order->save();
        return $order;
    }

    /**
     * Build or update an order from a collection of cart items.
     * Cart items are assumed to have frozen prices (unit_price, total).
     * This method only sets order totals and applies a coupon if present.
     */
    public static function make_order(Order $order, Collection $cartItems, array $data = [])
    {
        // Fill basic order attributes
        foreach ($data as $key => $value) {
            $order->$key = $value;
        }

        $order->user_id = auth()->id();
        $order->total = 0;
        $order->coupon_discount_applied = 0;

        // Attach cart items to the order and sum their totals
        foreach ($cartItems as $cartItem) {
            $cartItem->order_uuid = $order->uuid;
            $cartItem->save(); // only update the UUID, do not recalc prices
            $order->total += $cartItem->total;
        }

        // Apply coupon if present and valid
        if ($order->coupon_id) {
            $coupon = $order->coupon ?: Coupon::findOrFail($order->coupon_id);

            if ($coupon && $coupon->is_usable() && $order->total >= $coupon->min_order_value) {
                $order->total = DiscountHelpers::apply_discount($coupon, $order->total);
                $order->coupon_discount_applied = $coupon->applied_discount;
                $order->coupon_snapshot = Functions::get_coupon_snapshot($coupon);
            } else {
                $order->coupon_id = null;
                $order->coupon_snapshot = null;
            }
        }

        return $order;
    }
}
