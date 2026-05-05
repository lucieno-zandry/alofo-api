<?php

namespace App\Helpers;

use App\Enums\UserRole;
use App\Models\Image;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

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

    public static function get_frontend_url(?string $urls_config_key = null, string $user_role = 'CLIENT')
    {
        $fe_url_key = strtolower($user_role) === 'client'
            ? 'urls.front_office_fe_url'
            : 'urls.backoffice_fe_url';

        $fe_url = config($fe_url_key);                       // always defined

        $frontend_url = request()->get('origin', $fe_url);
        $lang = self::get_lang();
        $url = "$frontend_url/$lang";

        $pathname = $urls_config_key
            ? config("urls.{$urls_config_key}")   // e.g. config('urls.account_settings_pathname')
            : null;

        if ($pathname) {
            $url = "$url/$pathname";
        }

        return $url;
    }

    public static function get_order_detail_page_url(string $order_uuid): string
    {
        $frontend_url = self::get_frontend_url('customer_order_details_pathname');
        $redirect_url = $frontend_url . $order_uuid;

        return $redirect_url;
    }

    public static function get_user_detail_page_url(string|int $user_id)
    {
        $frontend_url = self::get_frontend_url('admin_user_details_pathname', UserRole::ADMIN->value);
        $url = $frontend_url . $user_id;

        return $url;
    }

    public static function setting(string $key, $default = null)
    {
        return Cache::tags('settings')->rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = Setting::find($key);
            return $setting ? $setting->value : $default;
        });
    }

    public static function format_money(int|float $number): string
    {
        $currency = app(SettingService::class)->get('currency', 'EUR');
        $formated = number_format($number, 2) . ' ' . $currency;

        return $formated;
    }

    /**
     * Extracts the username from an email address.
     *
     * @param string $email The email address to process.
     * @return string|null The username part, or null if invalid.
     */
    public static function get_email_username(string $email): ?string
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null; // Invalid email
        }

        // Split into username and domain
        $parts = explode('@', $email, 2);
        return $parts[0] ?? null;
    }
}
