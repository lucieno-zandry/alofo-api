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
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters, HasEffectivePrice;

    protected $fillable = [
        'discount',
        'type',
        'start_date',
        'end_date',
        'is_active'
    ];

    public $applied_promotion = 0;

    public function is_active(): bool
    {
        $now = now();
        return $this->start_date <= $now && $this->end_date > $now && $this->is_active;
    }

    public function scopeActive(Builder $builder): Builder
    {
        $now = now();

        $builder
            ->where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->where('is_active', true);

        return $builder;
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class);
    }

    public function convertCurrency()
    {
        if ($this?->type === DiscountType::FIXED_AMOUNT->value) {
            $this->setValueToConvertedCurrency('discount', $this->discount);
        }

        if ($this->relationLoaded('variants')) {
            /** @var \App\Models\Variant */
            foreach ($this->variants as $variant) {
                $variant->convertCurrency();
            }
        }

        return $this;
    }

    public function snapshot()
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'badge'    => $this->badge,         // optional badge text/identifier
            'discount' => $this->discount,
            'type'     => $this->type,
        ];
    }

    public function convertSnapshotCurrency(array $snapshot)
    {
        if ($snapshot['type'] === DiscountType::FIXED_AMOUNT->value)
            $snapshot['discount'] = app(CurrencyService::class)->convert($snapshot['discount']);

        return $snapshot;
    }

    public function convertSnapshotsCurrency(array $snapshots): array
    {
        foreach ($snapshots as $snapshot)
            $snapshot = $this->convertSnapshotCurrency($snapshot);

        return $snapshots;
    }
}
