<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'theme',
        'language',
        'timezone',
        'currency',
    ];

    protected $casts = [
        'theme' => 'string',
        'language' => 'string',
        'timezone' => 'string',
        'currency' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
