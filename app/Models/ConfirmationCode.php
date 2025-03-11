<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfirmationCode extends Model
{
    protected $fillable = [
        'content',
        'user_id',
        'expires_at'
    ];
}
