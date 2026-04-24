<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function content(): Attribute
    {
        return Attribute::make(
            get: function ($value) {

                if (is_string($value))
                    $value = json_decode($value, true);

                switch ($this->block_type) {
                    case 'collection_grid':
                        if ($value && isset($value['items']) && is_array($value['items'])) {
                            $items = [];

                            foreach ($value['items'] as $item) {
                                // Hydrate category
                                if (isset($item['category_id'])) {
                                    /** @var \App\Models\Category | null */
                                    $category = Category::find($item['category_id']);

                                    if ($category) {
                                        $category->append('cheapest_variant');
                                        $item['category'] = $category;
                                    }
                                }
                                // Hydrate image
                                if (isset($item['image_id'])) {
                                    $item['image'] = Image::find($item['image_id']);
                                }

                                $items[] = $item;
                            }

                            $value['items'] = $items;
                        }

                        break;
                }

                return $value;
            }
        );
    }
}
