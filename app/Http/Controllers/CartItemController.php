<?php

namespace App\Http\Controllers;

use App\Helpers\CartItemHelpers;
use App\Helpers\Functions;
use App\Helpers\OrderHelpers;
use App\Http\Requests\CartItemCreateRequest;
use App\Http\Requests\CartItemDeleteRequest;
use App\Http\Requests\CartItemUpdateRequest;
use App\Models\CartItem;
use App\Models\Promotion;
use App\Models\Variant;

class CartItemController extends Controller
{

    public function store(CartItemCreateRequest $request, Variant $variant)
    {
        $data = $request->validated();

        $data['user_id'] = auth()->id();
        $data['variant_id'] = $variant->id;
        $data['product_id'] = $variant->product_id;

        // make snapshot before loading relationships
        $data['variant_snapshot'] = json_encode($variant);

        $variant->load(['product', 'variant_options.variant_group']);

        $data['product_snapshot'] = [
            'id' => $variant->product->id,
            'title' => $variant->product->title,
            'slug' => $variant->product->slug,
            'category_id' => $variant->product->category_id,
            'main_image' => $variant->product->main_image,
        ];

        $variant_options_snapshot = Functions::get_variant_options_snapshot($variant->variant_options);

        $data['variant_options_snapshot'] = json_encode($variant_options_snapshot);

        $cart_item = CartItemHelpers::make_item(
            new CartItem(),
            $data,
            $request->promotion
        );

        $cart_item->save();

        return [
            'cart_item' => $cart_item
        ];
    }

    public function update(CartItemUpdateRequest $request, CartItem $cart_item)
    {
        $data = $request->validated();

        // Fill in the required data in order to make the cart item
        $data['variant_id'] = $cart_item->variant_id;

        if (!isset($data['count']))
            $data['count'] = $cart_item->count;

        CartItemHelpers::make_item(
            $cart_item,
            $data,
            $request->promotion
        )
            ->save();

        // Refresh the order to ajust informations
        if ($cart_item->order)
            OrderHelpers::refresh_order($cart_item->order);

        return [
            'cart_item' => $cart_item
        ];
    }

    public function destroy(CartItemDeleteRequest $request)
    {
        $cart_item_ids = explode(',', $request->cart_item_ids);

        $deleted = CartItem::whereIn('id', $cart_item_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function show(int $cart_item_id)
    {
        $cart_item = CartItem::withRelations()->find($cart_item_id);

        return [
            'cart_item' => $cart_item
        ];
    }

    public function index()
    {
        $cart_items = CartItem::applyFilters()
            ->where('user_id', auth()->id())
            ->notOrdered()
            ->get();

        return [
            'cart_items' => $cart_items
        ];
    }
}
