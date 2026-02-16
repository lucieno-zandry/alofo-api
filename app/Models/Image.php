<?php

namespace App\Models;

use App\Traits\ApplyFilters;
use App\Traits\DynamicConditionApplicable;
use App\Traits\WithOrdering;
use App\Traits\WithPagination;
use App\Traits\WithRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use WithPagination, WithRelationships, WithOrdering, DynamicConditionApplicable, ApplyFilters;

    protected $fillable = [
        'disk',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
    ];

    protected $hidden = [
        'path',
        'disk',
        'mime_type',
        'size',
        'created_at',
        'updated_at',
    ];

    protected $appends = ['url'];

    protected static function booted()
    {
        static::deleting(function (Image $image) {
            // Delete the physical file
            if ($image->path && Storage::disk($image->disk)->exists($image->path)) {
                Storage::disk($image->disk)->delete($image->path);
            }
        });
    }

    public function deleteIfUnused(): void
    {
        if ($this->products()->count() === 0) {
            Storage::disk($this->disk)->delete($this->path);
            $this->delete();
        }
    }

    public function getUrlAttribute()
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'imageable');
    }

    public function variant()
    {
        return $this->hasOne(Variant::class);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'avatar_image_id');
    }
}
