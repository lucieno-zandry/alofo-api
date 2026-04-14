<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

use function Illuminate\Log\log;

class SettingService
{
    /**
     * Retrieve a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get(string $key, $default = null)
    {
        
        $value = Cache::tags('settings')->rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = Setting::find($key);
            return $setting ? $setting->value : $default;
        });

        // log($value);

        return $value;
    }
}
