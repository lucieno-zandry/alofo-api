<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class Variant extends Model
{
    use HasFactory, WithPagination, WithOrdering, WithRelationships, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock',
        'image_id'
    ];

    // In app/Models/Variant.php

    protected $appends = ['effective_price']; // or add conditionally

    public function getEffectivePriceAttribute(): float
    {
        return $this->getEffectivePrice(); // uses currently authenticated user
    }

    /**
     * Get the effective price for the current user based on applicable promotions.
     *
     * @param \App\Models\User|null $user If null, uses auth()->user()
     * @return float
     */
    public function getEffectivePrice(?User $user = null): float
    {
        $user = $user ?? auth()->user();
        $basePrice = $this->price;

        // If no user or user cannot see special prices, return base price
        if (!$user || !$user->canUseSpecialPrices()) {
            return $basePrice;
        }

        // Load promotions if not already loaded
        if (!$this->relationLoaded('promotions')) {
            $this->load(['promotions' => fn($q) => $q->active()]);
        }

        $applicablePromotions = $this->promotions->filter(fn($promo) => $this->isPromotionApplicable($promo, $user));

        if ($applicablePromotions->isEmpty()) {
            return $basePrice;
        }

        return $this->calculateDiscountedPrice($basePrice, $applicablePromotions);
    }

    /**
     * Determine if a promotion is applicable to the given user.
     */
    protected function isPromotionApplicable(Promotion $promotion, User $user): bool
    {
        // Check applies_to rules
        return match ($promotion->applies_to) {
            'client_code_only' => $user->client_code_id !== null,
            'regular_only'     => $user->client_code_id === null,
            default             => true, // 'all'
        };
    }

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

    /**
     * Get all promotions that are currently applied (for badge display).
     */
    public function getAppliedPromotions(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();
        if (!$user || !$user->canUseSpecialPrices()) {
            return collect();
        }

        if (!$this->relationLoaded('promotions')) {
            $this->load(['promotions' => fn($q) => $q->active()]);
        }

        return $this->promotions->filter(fn($promo) => $this->isPromotionApplicable($promo, $user));
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
            $relations = explode(',', request('with'));

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
}
