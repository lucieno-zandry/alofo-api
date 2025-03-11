<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
  use SoftDeletes, WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

  protected $fillable = [
    'line1',
    'line2',
    'line3',
    'phone_number',
    'fullname',
    'is_default',
    'user_id'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function orders()
  {
    return $this->hasMany(Order::class);
  }
}
