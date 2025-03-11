<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use WithPagination, WithRelationships, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'filename'
    ];

    public function products()
    {
        return $this->morphedByMany(Product::class, 'imageable');
    }
}
