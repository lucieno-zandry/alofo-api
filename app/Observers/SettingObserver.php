<?php
// app/Observers/SettingObserver.php

namespace App\Observers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        Cache::tags('settings')->flush();
    }

    public function deleted(Setting $setting): void
    {
        Cache::tags('settings')->flush();
    }
}
