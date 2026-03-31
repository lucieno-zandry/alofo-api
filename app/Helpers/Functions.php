<?php

namespace App\Helpers;

use App\Enums\DiscountType;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Image;
use App\Services\CurrencyService;
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

    public static function store_uploaded_image(UploadedFile $file, string $path): Image
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

    public static function get_lang(): string
    {
        $lang = app()->getLocale() ?? 'en';
        return $lang;
    }

    public static function get_frontend_url(?string $env_pathname = null, string $user_role = 'CLIENT')
    {
        $env_fe_url = $user_role === 'CLIENT' ? env('FRONT_OFFICE_FE_URL') : env('BACKOFFICE_FE_URL');

        $frontend_url = request()->get('origin', $env_fe_url);
        $lang = self::get_lang();
        $url = "$frontend_url/$lang";

        $pathname = $env_pathname ? env($env_pathname) : null;

        if ($pathname)
            $url = "$url/$pathname";

        return $url;
    }

    public static function get_order_detail_page_url(string $order_uuid): string
    {
        $frontend_url = self::get_frontend_url('CUSTOMER_ORDER_DETAILS_PATHNAME');
        $redirect_url = $frontend_url . $order_uuid;

        return $redirect_url;
    }
}
