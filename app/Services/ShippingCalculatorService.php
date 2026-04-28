<?php

namespace App\Services;

use App\Models\Address;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ShippingCalculatorService
{
    protected ?ShippingMethod $method = null;
    protected Address $address;
    protected Collection $items; // each item has weight_kg, quantity, etc.
    protected float $subtotal = 0; // order subtotal (for free shipping threshold)
    protected float $totalWeight = 0;

    public function __construct(protected CurrencyService $currencyService) {}

    public function setCurrencyService(CurrencyService $currencyService): self
    {
        $this->currencyService = $currencyService;
        return $this;
    }

    protected function invertItemsCurrency(Collection $items)
    {
        return $items->map(function ($item) {
            return $this->currencyService->invert($item['price']);
        });
    }

    /**
     * Set the shipping method to use.
     */
    public function setMethod(ShippingMethod $method): self
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set the delivery address.
     */
    public function setAddress(Address $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Set cart items (or order items) each with weight_kg and quantity.
     * Item expected to have: weight_kg (float), quantity (int), price? (for subtotal)
     */
    public function setItems(Collection $items): self
    {
        $this->items = $this->invertItemsCurrency($items);
        $this->totalWeight = $items->sum(fn($item) => ($item['weight_kg'] ?? 0) * ($item['quantity'] ?? 1));
        $this->subtotal = $items->sum(fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 1));
        return $this;
    }

    /**
     * Calculate cost for the currently set method, address, and items.
     */
    public function calculate(): float
    {
        if (!$this->method) {
            throw new \RuntimeException('No shipping method selected');
        }

        // Check if country is allowed
        if ($this->method->allowed_countries && !in_array($this->address->country, $this->method->allowed_countries)) {
            throw new \RuntimeException('Shipping method not available for this country');
        }

        // Free shipping threshold
        if ($this->method->free_shipping_threshold && $this->subtotal >= $this->method->free_shipping_threshold) {
            return 0.0;
        }

        // 1. Try to find a matching zone rate (from shipping_rates)
        $zoneRate = $this->getMatchingZoneRate();
        if ($zoneRate) {
            return $this->calculateFromZoneRate($zoneRate);
        }

        // 2. Fall back to method's own calculation based on its calculation_type
        return match ($this->method->calculation_type) {
            'flat_rate' => $this->calculateFlatRate(),
            'weight_based' => $this->calculateWeightBased(),
            'api' => $this->calculateViaApi(),
            default => 0.0,
        };
    }

    /**
     * Calculate cost from a matching shipping_rate row.
     */
    protected function calculateFromZoneRate(ShippingRate $rate): float
    {
        $cost = (float) $rate->rate;

        // For weight_based methods, add extra per kg beyond min_weight
        if ($this->method->calculation_type === 'weight_based' && $rate->rate_per_kg && $this->totalWeight > ($rate->min_weight_kg ?? 0)) {
            $extraKg = $this->totalWeight - ($rate->min_weight_kg ?? 0);
            $cost += $extraKg * (float) $rate->rate_per_kg;
        }

        return $cost;
    }

    /**
     * Get all available shipping methods with their calculated costs.
     * @return array [['method' => ShippingMethod, 'cost' => float], ...]
     */
    public function getAvailableMethods(): array
    {
        $methods = ShippingMethod::where('is_active', true)->get();
        $available = [];

        foreach ($methods as $method) {
            try {
                $this->setMethod($method);
                $cost = $this->calculate();
                $available[] = [
                    'method' => $method,
                    'cost' => $this->currencyService->convert($cost),
                ];
            } catch (\Exception $e) {
                // Method not available for this address or other reason - skip
                Log::info("Shipping method {$method->name} not available: " . $e->getMessage());
                continue;
            }
        }

        return $available;
    }

    protected function calculateFlatRate(): float
    {
        return (float) ($this->method->flat_rate ?? 0);
    }

    protected function calculateWeightBased(): float
    {
        // Fallback to method's rate_per_kg (no zone rate matched)
        if ($this->method->rate_per_kg) {
            return $this->totalWeight * (float) $this->method->rate_per_kg;
        }

        return 0.0;
    }

    protected function calculateViaApi(): float
    {
        $carrier = $this->method->carrier;

        return match ($carrier) {
            'fedex' => $this->getFedExRate(),
            'colissimo' => $this->getColissimoRate(),
            default => 0.0,
        };
    }

    protected function getMatchingZoneRate(): ?ShippingRate
    {
        $country = $this->address->country;
        $city = $this->address->city;

        $query = ShippingRate::where('shipping_method_id', $this->method->id)
            ->where(function ($q) use ($country) {
                $q->where('country_code', $country)->orWhere('country_code', '*');
            });

        // Try to match city pattern if present
        $rates = $query->get();

        foreach ($rates as $rate) {
            if ($rate->city_pattern && !preg_match('/' . $rate->city_pattern . '/i', $city)) {
                continue;
            }
            if ($rate->min_weight_kg && $this->totalWeight < $rate->min_weight_kg) {
                continue;
            }
            if ($rate->max_weight_kg && $this->totalWeight > $rate->max_weight_kg) {
                continue;
            }
            return $rate;
        }

        return null;
    }

    // ========== API Carrier Stubs ==========

    protected function getFedExRate(): float
    {
        // TODO: Implement real FedEx API call using config('services.fedex')
        Log::info('FedEx rate requested', ['weight' => $this->totalWeight, 'country' => $this->address->country]);
        return max(10.0, $this->totalWeight * 1.5);
    }

    protected function getColissimoRate(): float
    {
        // TODO: Implement real Colissimo API
        Log::info('Colissimo rate requested', ['weight' => $this->totalWeight, 'country' => $this->address->country]);
        return max(7.0, $this->totalWeight * 1.2);
    }

    // ========== Helper to get total weight ==========

    public function getTotalWeight(): float
    {
        return $this->totalWeight;
    }
}
