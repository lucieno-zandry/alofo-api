<?php

namespace App\Traits;

trait ApplyFilters
{
    use WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable;

    public function scopeApplyFilters($query)
    {
        $query->withRelations();
        $query->withPagination();
        $query->withOrdering();
        $query->dynamicConditionApplicable();
        return $query;
    }
}
