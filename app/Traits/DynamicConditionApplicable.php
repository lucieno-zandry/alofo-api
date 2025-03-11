<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait DynamicConditionApplicable
{
    public function scopeDynamicConditionApplicable(Builder $query): Builder
    {
        $whereClause = request('where');

        // Split conditions by commas
        $conditions = explode(',', $whereClause);

        foreach ($conditions as $condition) {
            // Use regex to match the field, operator, and value
            if (preg_match('/^([^<>=!]+)([<>!=]*=|[<>!]+)(.+)$/', $condition, $matches)) {
                $field = $matches[1];
                $operator = $matches[2]; // Includes support for '='
                $value = $matches[3];

                // Apply the condition to the query
                $query->where($field, $operator, $value);
            }
        }

        return $query;
    }
}
