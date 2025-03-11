<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

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

}
