<?php

namespace App\Helpers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Image;
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

    public static function store_uploaded_file(UploadedFile $file, string $path): Image
    {
        $path = $file->store(
            $path,
            'public'
        );

        $image = Image::create([
            'path'      => $path,
            'disk'      => 'public',
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize(),
            'width'     => getimagesize($file)[0] ?? null,
            'height'    => getimagesize($file)[1] ?? null,
        ]);

        return $image;
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

    public static function get_address_snapshot(Address $address): array
    {
        return [
            'id'           => $address->id,
            'fullname'     => $address->fullname,
            'line1'        => $address->line1,
            'line2'        => $address->line2,
            'line3'        => $address->line3,
            'phone_number' => $address->phone_number,
        ];
    }

    public static function get_coupon_snapshot(?Coupon $coupon): ?array
    {
        if (!$coupon) return null;

        return [
            'id'              => $coupon->id,
            'code'            => $coupon->code,
            'type'            => $coupon->type,
            'discount'        => $coupon->discount,
            'min_order_value' => $coupon->min_order_value,
        ];
    }

    public static function get_frontend_url(?string $env_pathname)
    {
        $frontend_url = request('Origin') ?? env('FRONTEND_URL');
        $lang = app()->getLocale();
        $url = "$frontend_url/$lang";

        $pathname = env($env_pathname);

        if ($pathname)
            $url = "$url/$pathname";

        return $url;
    }
}
