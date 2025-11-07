<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Storage;

class Functions
{
    public static function remove_keys_if_null(array $keys, array $data)
    {
        foreach ($keys as $key) {
            if (key_exists($key, $data) && !$data[$key]) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    public static function store_uploaded_file(UploadedFile $file, array $options = [])
    {
        if (empty($options['folder']))
            $options['folder'] = 'users';

        $path = $file->store($options['folder'], $options);
        return $path;
    }

    public static function get_iso_string(\DateTime $datetime): string
    {
        return $datetime->format('Y-m-d H:i:s');
    }

    public static function array_from(array $static, array $input, string $key)
    {
        $output = [];

        foreach ($input as $value) {
            $output[] = array_merge($static, [$key => $value]);
        }

        return $output;
    }

    public static function create_many(array $data, mixed $Model): Collection
    {
        $collection = new Collection();

        foreach ($data as $attributes) {
            $to_insert = $attributes;

            if (!is_array($attributes)) {
                $to_insert = $data;
            }

            $collection->add($Model->create($to_insert));
        }

        return $collection;
    }

    public static function sanitize_search_query(string $query): string
    {
        return collect(explode(' ', $query))
            ->map(fn($word) => "+" . $word . "*") // Adds full-text wildcards
            ->implode(' ');
    }

    /**
     * @param VariantOption[] $variant_options
     * @return Map[]
     */
    public static function get_variant_options_snapshot($variant_options)
    {
        $variant_options_snapshot = [];

        foreach ($variant_options as $option) {
            $groupName = $option->variant_group->name;
            $variant_options_snapshot[$groupName] = $option->value;
        }

        return $variant_options_snapshot;
    }
}
