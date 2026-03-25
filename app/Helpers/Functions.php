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
            'id'             => $address->id,
            'user_id'        => $address->user_id,
            'label'          => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone'          => $address->phone,
            'phone_alt'      => $address->phone_alt,
            'line1'          => $address->line1,
            'line2'          => $address->line2,
            'city'           => $address->city,
            'state'          => $address->state,
            'postal_code'    => $address->postal_code,
            'country'        => $address->country,
            'address_type'   => $address->address_type,
            'is_default'     => $address->is_default,
            'created_at'     => $address->created_at,
            'updated_at'     => $address->updated_at,
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
