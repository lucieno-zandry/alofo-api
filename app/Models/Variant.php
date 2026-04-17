<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Services\CurrencyService;
use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\HasEffectivePrice;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class Variant extends Model
{
    use HasFactory, WithPagination, WithOrdering, WithRelationships, DynamicConditionApplicable, ApplyFilters, HasEffectivePrice;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'image_id'
    ];

    protected $appends = [
        'effective_price',
        'applied_promotions',
    ];

    /**
     * Calculate final price by applying promotions according to stacking rules.
     */
    protected function calculateDiscountedPrice(float $basePrice, Collection $promotions): float
    {
        $price = $basePrice;

        // Separate stackable and non‑stackable promotions
        $stackable = $promotions->where('stackable', true);
        $nonStackable = $promotions->where('stackable', false);

        // Apply the best non‑stackable promotion (lowest resulting price)
        if ($nonStackable->isNotEmpty()) {
            $bestPrice = $basePrice;
            foreach ($nonStackable as $promo) {
                $candidate = $this->applySinglePromotion($basePrice, $promo);
                if ($candidate < $bestPrice) {
                    $bestPrice = $candidate;
                }
            }
            $price = $bestPrice;
        }

        // Apply all stackable promotions in the correct order (percentages first, then fixed)
        $stackable = $stackable->sortBy(function ($promo) {
            // Percentages first (value 1), then fixed (value 2)
            return $promo->type === 'PERCENTAGE' ? 1 : 2;
        });

        foreach ($stackable as $promo) {
            $price = $this->applySinglePromotion($price, $promo);
            // Apply maximum discount cap if present
            if ($promo->max_discount !== null) {
                $maxDiscount = $promo->max_discount;
                if (($basePrice - $price) > $maxDiscount) {
                    $price = $basePrice - $maxDiscount;
                    break; // stop applying further promotions after cap reached
                }
            }
        }

        return max($price, 0); // ensure non‑negative
    }

    /**
     * Get the effective price for the current user based on applicable promotions.
     *
     * @param \App\Models\User|null $user If null, uses auth()->user()
     * @return float
     */
    public function getEffectivePriceAttribute(): float
    {
        $user = auth('sanctum')->user();
        $basePrice = $this->price;
        $effectivePrice = $basePrice;

        // Load promotions if not already loaded
        if (!$this->relationLoaded('promotions')) {
            $this->load(['promotions' => fn($q) => $q->active()]);
        }

        /** @var Collection */
        $applicablePromotions = $this->promotions->filter(fn($promo) => $this->isPromotionApplicable($promo, $user));

        if ($applicablePromotions->isNotEmpty()) {
            $effectivePrice = $this->calculateDiscountedPrice($basePrice, $applicablePromotions);
        }

        return $effectivePrice;
    }

    /**
     * Determine if a promotion is applicable to the given user (may be null for guests).
     */
    protected function isPromotionApplicable(Promotion $promotion, ?User $user): bool
    {
        // Guest users: only 'all' promotions are applicable
        if (!$user) {
            return $promotion->applies_to === 'all';
        }

        // Authenticated user logic based on client_code_id
        return match ($promotion->applies_to) {
            'client_code_only' => $user->client_code_id !== null,
            'regular_only'     => $user->client_code_id === null,
            default            => true, // 'all'
        };
    }

    /**
     * Get all promotions that are currently applied (for badge display).
     */
    public function getAppliedPromotionsAttribute(): Collection
    {
        /** @var \App\Models\User */
        $user = auth('sanctum')->user();

        if (!$this->relationLoaded('promotions')) {
            $this->load(['promotions' => fn($q) => $q->active()]);
        }

        if ($user?->roleIsAdmin())
            return $this->promotions;

        return $this->promotions
            ->filter(fn($promo) => $this->isPromotionApplicable($promo, $user));
    }

    /**
     * Apply a single promotion to a given price.
     */
    protected function applySinglePromotion(float $currentPrice, Promotion $promotion): float
    {
        if ($promotion->type === 'PERCENTAGE') {
            return $currentPrice * (1 - $promotion->discount / 100);
        } else { // FIXED_AMOUNT
            return $currentPrice - $promotion->discount;
        }
    }


    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant_options()
    {
        return $this->belongsToMany(VariantOption::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    // Existing scopes and other methods remain unchanged...
    public function scopeWithRelations(Builder $query)
    {
        if (request()->has('with')) {
            $relations = request('with');

            foreach ($relations as $relation) {
                $query->with([
                    $relation => function ($query) use ($relation) {
                        if ($relation === 'promotions') {
                            $query->active();
                        }
                        $query->latest('id');
                    }
                ]);
            }
        }

        return $query;
    }

    public function convertCurrency(): static
    {
        $this->setValuesToConvertedCurrency([
            'price' => $this->price,
            'effective_price' => $this->effective_price,
        ]);

        if ($this->relationLoaded('product')) {
            $this->product->convertCurrency();
        }

        if ($this->applied_promotions) {
            /** @var \App\Models\Promotion */
            foreach ($this->applied_promotions as $promotion) {
                $promotion->convertCurrency();
            }
        }

        return $this;
    }

    // Variant snapshot (base price only, effective price is stored separately)
    public function snapshot(): array
    {
        return [
            'id'    => $this->id,
            'sku'   => $this->sku,
            'price' => $this->price,
            'image' => $this->image?->url ?? null,
            'weight_kg' => $this->weight_kg,
            'length_cm' => $this->length_cm,
            'width_cm' => $this->width_cm,
            'height_cm' => $this->height_cm,
        ];
    }

    public function convertSnapshotCurrency(array $snapshot): array
    {
        $snapshot['price'] = app(CurrencyService::class)->convert($snapshot['price']);
        return $snapshot;
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        // Product filter
        if ($productId = $filters['product_id'] ?? null) {
            $query->where('product_id', $productId);
        }

        // SKU partial match
        if ($sku = $filters['sku'] ?? null) {
            $query->where('sku', 'like', "%{$sku}%");
        }

        // Price range
        if ($minPrice = $filters['min_price'] ?? null) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $filters['max_price'] ?? null) {
            $query->where('price', '<=', $maxPrice);
        }

        // Stock range
        if ($minStock = $filters['min_stock'] ?? null) {
            $query->where('stock', '>=', $minStock);
        }
        if ($maxStock = $filters['max_stock'] ?? null) {
            $query->where('stock', '<=', $maxStock);
        }

        // Low stock (stock < 5, can be configurable)
        if (isset($filters['low_stock'])) {
            $threshold = 5; // or from settings
            $query->where('stock', '<', $threshold);
        }

        // Global search (SKU or product title)
        if ($search = $filters['search'] ?? null) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhereHas('product', function (Builder $pq) use ($search) {
                        $pq->where('title', 'like', "%{$search}%");
                    });
            });
        }
    }
}
