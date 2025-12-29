<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait DynamicConditionApplicable
{
    public function scopeDynamicConditionApplicable(Builder $query): Builder
    {
        $whereClause = request('where');

        if (empty($whereClause)) {
            return $query;
        }

        // Split conditions by commas
        $conditions = explode(',', $whereClause);

        foreach ($conditions as $condition) {
            // Regex captures: 1. Field Name, 2. Operator, 3. Value
            if (preg_match('/^([^<>=!:]+)([<>!=]*=|[:<>!]+)(.+)$/', $condition, $matches)) {
                $field = trim($matches[1]);
                $operator = $matches[2];
                $value = trim($matches[3]);

                // Check for whereIn syntax (e.g., status:1|2|3 or field:value1,value2)
                // Using ':' as a shorthand for 'IN' or checking if value contains a pipe '|'
                if ($operator === ':' || str_contains($value, '|')) {
                    $values = explode('|', $value);
                    $query->whereIn($field, $values);
                } else {
                    // Standard where clause
                    $query->where($field, $operator, $value);
                }
            }
        }

        return $query;
    }
}
