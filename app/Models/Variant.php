<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\HasEffectivePrice;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

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
    public function getEffectivePrice(?User $user = null): float
    {
        $user = $user ?? auth()->user();
        $basePrice = $this->price;

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
    public function getAppliedPromotions(?User $user = null): Collection
    {
        $user = $user ?? auth()->user();

        if (!$this->relationLoaded('promotions')) {
            $this->load(['promotions' => fn($q) => $q->active()]);
        }

        return $this->promotions->filter(fn($promo) => $this->isPromotionApplicable($promo, $user));
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
