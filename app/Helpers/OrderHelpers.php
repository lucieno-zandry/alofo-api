<?php

namespace App\Helpers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ShippingMethod;
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
    public static function make_order(Order $order, Collection $cartItems, array $data = [], ?float $shippingCost = 0, ?ShippingMethod $shippingMethod = null)
    {
        // Fill basic attributes (address_id, coupon_id, shipping_method_id)
        foreach ($data as $key => $value) {
            $order->$key = $value;
        }

        $order->user_id = auth('sanctum')->id();
        $order->total = 0;
        $order->coupon_discount_applied = 0;

        // Attach cart items and sum their totals (subtotal)
        foreach ($cartItems as $cartItem) {
            $cartItem->order_uuid = $order->uuid;
            $cartItem->save();
            $order->total += $cartItem->total;  // $order->total becomes subtotal
        }

        $subtotal = $order->total;

        // Apply coupon if present
        if ($order->coupon_id) {
            $coupon = $order->coupon ?: Coupon::findOrFail($order->coupon_id);
            if ($coupon && $coupon->is_usable() && $subtotal >= $coupon->min_order_value) {
                $discountedSubtotal = DiscountHelpers::apply_discount($coupon, $subtotal);
                $order->coupon_discount_applied = $coupon->applied_discount;
                $order->coupon_snapshot = $coupon->snapshot();
            } else {
                $order->coupon_id = null;
                $order->coupon_snapshot = null;
                $discountedSubtotal = $subtotal;
            }
        } else {
            $discountedSubtotal = $subtotal;
        }

        // Add shipping cost
        $order->total = $discountedSubtotal + $shippingCost;

        // Optionally store shipping method snapshot if passed
        if ($shippingMethod) {
            $order->shipping_method_snapshot = $shippingMethod->snapshot();
            $order->shipping_method_id = $shippingMethod->id;
            $order->shipping_cost = $shippingCost;
        }

        return $order;
    }
}
