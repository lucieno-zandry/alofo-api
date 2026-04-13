<?php

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    public function currency(): Attribute
    {
        $service = app(CurrencyService::class);

        return Attribute::make(get: function ($currency) use ($service) {
            if ($service->isAllowed())
                return $currency;

            return $service->getFrom();
        });
    }
}
