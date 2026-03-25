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
    'user_id',
    'label',
    'recipient_name',
    'phone',
    'phone_alt',
    'line1',
    'line2',
    'city',
    'state',
    'postal_code',
    'country',
    'address_type',
    'is_default',
  ];

  protected $casts = [
    'is_default' => 'boolean',
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
