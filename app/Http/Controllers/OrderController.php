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
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(OrderCreateRequest $request)
    {
        $data = $request->only(['address_id', 'coupon_id']);

        $cart_items = CartItem::whereIn('id', $request->cart_item_ids)
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

        $order->address_snapshot = $address->snapshot();

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

    public function index(Request $request)
    {
        $query = Order::query();

        // Eager load relationships if requested (e.g., ?with=user,shipments)
        if ($request->has('with')) {
            $relations = explode(',', $request->with);
            $query->with($relations);
        }

        // Apply customer filter (if role is customer)
        $query->customerFilterable();

        // Apply sorting from 'sort' parameter (e.g., 'created_at' or '-created_at')
        if ($request->has('sort')) {
            $sort = $request->sort;
            $direction = 'asc';
            if (str_starts_with($sort, '-')) {
                $direction = 'desc';
                $sort = substr($sort, 1);
            }
            $query->orderBy($sort, $direction);
        }

        // Apply search (if you need to implement it)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply date range filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply payment status filter (you'll need to join/whereHas transactions)
        if ($request->has('payment_status') && !empty($request->payment_status)) {
            $query->whereHas('transactions', function ($tQuery) use ($request) {
                $tQuery->where('status', $request->payment_status);
            });
        }

        // Apply shipment status filter
        if ($request->has('shipment_status') && !empty($request->shipment_status)) {
            $query->whereHas('shipments', function ($sQuery) use ($request) {
                $sQuery->where('status', $request->shipment_status);
            });
        }

        if ($request->has('total_min')) {
            $query->where('total', '>=', $request->total_min);
        }

        if ($request->has('total_max')) {
            $query->where('total', '<=', $request->total_max);
        }

        // Paginate using standard Laravel pagination
        $perPage = $request->get('per_page', 20);
        $orders = $query->paginate($perPage);

        /** @var \App\Models\Order */
        foreach ($orders as $order) {
            $order->convertCurrency();
        }

        return response()->json($orders);
    }

    public function show(string $order_uuid)
    {
        /** @var \App\Models\Order | null*/
        $order = Order::withRelations()->where('uuid', $order_uuid)->first();

        if ($order?->has_no_successful_payment())
            $order = OrderHelpers::refresh_order($order);

        $order?->convertCurrency();

        return [
            'order' => $order
        ];
    }
}
