<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    /** @use HasFactory<\Database\Factories\VariantOptionFactory> */
    use HasFactory, WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'value',
        'variant_group_id'
    ];

    public function variants()
    {
        return $this->belongsToMany(Variant::class);
    }

    public function variant_group()
    {
        return $this->belongsTo(VariantGroup::class);
    }

    /**
     * @param VariantOption[] $variant_options
     * @return Map[]
     */
    public function snapshots($variant_options)
    {
        $variant_options_snapshot = [];

        foreach ($variant_options as $option) {
            $groupName = $option->variant_group->name;
            $variant_options_snapshot[$groupName] = $option->value;
        }

        return $variant_options_snapshot;
    }
}
