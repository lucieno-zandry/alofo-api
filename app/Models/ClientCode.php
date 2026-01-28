<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ClientCode extends Model
{
    use WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    public function user()
    {
        return $this->hasMany(User::class);
    }

    protected $fillable = [
        'code',
        'user_id',
        'is_active',
        'max_uses',
    ];

    public function scopeCanBeUsed(Builder $query)
    {
        return $query->where('is_active', true)
            ->whereColumn('uses', '<', 'max_uses');
    }

    public function isUsable(): bool
    {
        return $this->is_active && ($this->uses < $this->max_uses);
    }

    public function incrementUses(): void
    {
        $this->uses = $this->uses + 1;
        $this->save();
    }
}
