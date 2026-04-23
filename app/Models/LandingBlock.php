<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingBlock extends Model
{
    protected $table = 'landing_blocks';

    protected $fillable = [
        'block_type',
        'title',
        'subtitle',
        'content',
        'landing_able_type',
        'landing_able_id',
        'image_id',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',      // JSONB cast to array/object
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    // Polymorphic relation to any model (Product, Variant, Category, AppImage, etc.)
    public function landing_able(): MorphTo
    {
        return $this->morphTo();
    }

    // Direct relation to AppImage (common case)
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class, 'image_id');
    }
}
