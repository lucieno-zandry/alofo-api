<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Helpers\OrderHelpers;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderDeleteRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(OrderCreateRequest $request)
    {
        $data = $request->only(['address_id', 'coupon_id']);

        $cart_items = CartItem::with('promotion')
            ->whereIn('id', $request->cart_item_ids)
            ->notOrdered()
            ->get();

        if ($cart_items->isEmpty())
            abort(403, "This cart has already been ordered.");

        $order = new Order();
        $order->uuid = Str::uuid()->toString();

        // Build order (totals, discounts, etc.)
        $order = OrderHelpers::make_order($order, $cart_items, $data);

        // 🔹 Address snapshot
        $address = Address::where('id', $data['address_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $order->address_snapshot = Functions::get_address_snapshot($address);

        $order->save();

        return [
            'order' => $order
        ];
    }

    public function update(OrderUpdateRequest $request, Order $order)
    {
        $data = $request->validated();

        $order->update($data);

        return [
            'order' => $order
        ];
    }

    public function destroy(OrderDeleteRequest $request)
    {
        $order_uuids = explode(',', $request->order_uuids);
        $deleted = Order::whereIn('uuid', $order_uuids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $orders = Order::applyFilters()
            ->clientFilterable()
            ->get();

        return [
            'orders' => $orders
        ];
    }

    public function show(string $order_uuid)
    {
        $order = Order::withRelations()->where('uuid', $order_uuid)->first();

        if ($order?->has_no_successful_payment())
            $order = OrderHelpers::refresh_order($order);

        return [
            'order' => $order
        ];
    }
}
