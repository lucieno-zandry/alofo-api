<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
  use WithRelationships, WithPagination, WithOrdering, DynamicConditionApplicable, ApplyFilters;

  protected $fillable = [
    'user_id',
    'reason',
    'set_by',
    'status',
    'expires_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function set_by_user()
  {
    return $this->belongsTo(User::class, 'set_by');
  }
}
