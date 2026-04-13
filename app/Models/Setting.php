<?php
// app/Models/Setting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'value' => 'json',
        'is_public' => 'boolean',
    ];

    /**
     * Accessor that casts the stored JSON value to the declared type.
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $decoded = json_decode($value, true);
                return $this->castValue($decoded, $this->type);
            },
            set: function ($value, $attributes = []) {
                // Determine the type: first try the model's already set type,
                // then fall back to the type from the attributes being saved,
                // and finally default to 'string' if none provided.
                $type = $this->type ?? $attributes['type'] ?? 'string';

                $this->validateValueForType($value, $type);
                return json_encode($value);
            }
        );
    }

    /**
     * Validate that the given value matches the expected type.
     */
    protected function validateValueForType($value, string $type): void
    {
        match ($type) {
            'string'  => null,
            'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'float'   => is_numeric($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', true, false], true),
            'json'    => is_array($value) || is_object($value),
            default   => throw new \InvalidArgumentException("Invalid setting type: {$type}")
        };
    }

    /**
     * Cast the decoded JSON value to the declared PHP type.
     */
    protected function castValue($value, string $type)
    {
        return match ($type) {
            'string'  => (string) $value,
            'integer' => (int) $value,
            'float'   => (float) $value,
            'boolean' => (bool) $value,
            'json'    => $value,
            default   => $value,
        };
    }

    /**
     * Scope a query to only include public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
