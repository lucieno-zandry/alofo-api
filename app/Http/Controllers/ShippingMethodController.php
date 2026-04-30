<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShippingMethodRequest;
use App\Http\Requests\UpdateShippingMethodRequest;
use App\Http\Requests\StoreShippingRateRequest;
use App\Http\Requests\UpdateShippingRateRequest;
use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\SettingService;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\Request;


class ShippingMethodController extends Controller
{
    /**
     * List all shipping methods (admin).
     */
    public function index()
    {
        $methods = ShippingMethod::with('shipping_rates')->paginate(20);
        return response()->json($methods);
    }

    /**
     * Store a new shipping method.
     */
    public function store(StoreShippingMethodRequest $request)
    {
        $data = $request->validated();
        $method = ShippingMethod::create($data);
        return response()->json($method, 201);
    }

    /**
     * Show a single shipping method.
     */
    public function show(ShippingMethod $shippingMethod)
    {
        $shippingMethod->load('shipping_rates');
        return response()->json($shippingMethod);
    }

    /**
     * Update a shipping method.
     */
    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod)
    {
        $shippingMethod->update($request->validated());
        return response()->json($shippingMethod);
    }

    /**
     * Delete a shipping method (and its rates).
     */
    public function destroy(ShippingMethod $shippingMethod)
    {
        $shippingMethod->delete();
        return response()->json(null, 204);
    }

    // ========== Nested Rates ==========

    /**
     * List rates for a specific shipping method.
     */
    public function indexRates(ShippingMethod $shippingMethod)
    {
        $rates = $shippingMethod->shipping_rates()->paginate(20);
        return response()->json($rates);
    }

    /**
     * Store a new rate for a shipping method.
     */
    public function storeRate(StoreShippingRateRequest $request, ShippingMethod $shippingMethod)
    {
        $data = $request->validated();
        $data['shipping_method_id'] = $shippingMethod->id;
        $rate = ShippingRate::create($data);
        return response()->json($rate, 201);
    }

    /**
     * Show a specific rate.
     */
    public function showRate(ShippingMethod $shippingMethod, ShippingRate $rate)
    {
        if ($rate->shipping_method_id !== $shippingMethod->id) {
            return response()->json(['message' => 'Rate not found for this method'], 404);
        }
        return response()->json($rate);
    }

    /**
     * Update a rate.
     */
    public function updateRate(UpdateShippingRateRequest $request, ShippingMethod $shippingMethod, ShippingRate $rate)
    {
        if ($rate->shipping_method_id !== $shippingMethod->id) {
            return response()->json(['message' => 'Rate not found for this method'], 404);
        }
        $rate->update($request->validated());
        return response()->json($rate);
    }

    /**
     * Delete a rate.
     */
    public function destroyRate(ShippingMethod $shippingMethod, ShippingRate $rate)
    {
        if ($rate->shipping_method_id !== $shippingMethod->id) {
            return response()->json(['message' => 'Rate not found for this method'], 404);
        }
        $rate->delete();
        return response()->json(null, 204);
    }

    // ========== Public endpoint for checkout ==========

    /**
     * Get available shipping methods for a given address and cart.
     * (Public – no admin required)
     */
    public function getAvailableMethods(Request $request, ShippingCalculatorService $calculator)
    {
        $validated = $request->validate([
            'address_id' => 'exists:addresses,id',
            'cart_items' => 'required|array',
            'cart_items.*.weight_kg' => 'nullable|numeric|min:0',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.price' => 'nullable|numeric|min:0',
            'location' => 'nullable|array',
            'location.country' => 'string|size:2',
            'location.city' => 'string'
        ]);

        $address = null;
        $methods = [];
        $location = [];

        if ($request->address_id) {
            $address = Address::findOrFail($request->address_id);

            // Ensure address belongs to authenticated user
            if ($address->user_id !== auth('sanctum')->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $location = [
                'country' => $address->country,
                'city' => $address->city,
            ];
        } else if (isset($validated['location'])) {
            $location = $validated['location'];

            $address = new Address($location);
        } else {
            $geolocated = $calculator->geolocateip($request->ip());
            $settings = app(SettingService::class);

            $location = [
                'country' => $geolocated['country_code'] ?? $settings->get('default_country', 'FR'),  // e.g., 'FR'
                'city'    => $geolocated['city_name'] ?? $settings->get('default_city', 'Paris'),     // e.g., 'Paris'
            ];

            $address = new Address($location);
        }

        $items = collect($request->cart_items);

        $calculator
            ->setAddress($address)
            ->setItems($items);

        $methods['available'] = $calculator->getAvailableMethods();
        $methods['location'] = $location;

        return response()->json($methods);
    }
}
