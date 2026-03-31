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

  public function snapshot()
  {
    return [
      'id'             => $this->id,
      'user_id'        => $this->user_id,
      'label'          => $this->label,
      'recipient_name' => $this->recipient_name,
      'phone'          => $this->phone,
      'phone_alt'      => $this->phone_alt,
      'line1'          => $this->line1,
      'line2'          => $this->line2,
      'city'           => $this->city,
      'state'          => $this->state,
      'postal_code'    => $this->postal_code,
      'country'        => $this->country,
      'address_type'   => $this->address_type,
      'is_default'     => $this->is_default,
      'created_at'     => $this->created_at,
      'updated_at'     => $this->updated_at,
    ];
  }
}
