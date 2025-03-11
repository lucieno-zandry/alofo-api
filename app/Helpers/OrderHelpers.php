<?php

namespace App\Helpers;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderHelpers
{

    public static function refresh_order(Order $order)
    {
        return self::make_order($order, $order->cart_items)->save();
    }

    public static function make_order(Order $order, Collection $cart_items, array $data = [])
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $order->$key = $value;
            }
        }

        $order->user_id = auth()->id();
        $order->total = 0;
        $order->coupon_discount_applied = 0;

        foreach ($cart_items as $cart_item) {
            $cart_item->order_uuid = $order->uuid;

            if ($cart_item->promotion && !$cart_item->promotion->is_active()) {
                $cart_item = CartItemHelpers::make_item($cart_item, ['promotion_id' => null]);
            }

            $cart_item->save();
            $order->total += $cart_item->total;
        }

        if ($order->coupon_id) {
            $coupon = $order->coupon ?: Coupon::find($order->coupon_id);

            if ($coupon) {
                if ($order->total >= $coupon->min_order_value) {
                    $order->total = DiscountHelpers::apply_discount($coupon, $order->total);
                    $order->coupon_discount_applied = $coupon->applied_discount;
                } else {
                    $order->coupon_id = null;
                }
            }
        }

        return $order;
    }
}