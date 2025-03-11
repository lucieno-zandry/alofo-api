<?php

namespace App\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;

trait WithRelationships
{
    public function scopeWithRelations(Builder $query)
    {
        if (request()->has('with')) {
            $relations = explode(',', request('with'));

            foreach ($relations as $relation) {
                $query->with([
                    $relation => function ($query) {
                        $query->latest('id');
                    }
                ]);
            }
        }

        return $query;
    }
}