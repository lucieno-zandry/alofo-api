<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;

class ClientCode extends Model
{
    use WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    public function user()
    {
        return $this->hasOne(User::class);
    }

    protected $fillable = [
        'code',
        'user_id'
    ];
}
