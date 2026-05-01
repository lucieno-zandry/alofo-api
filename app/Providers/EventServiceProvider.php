<?php

namespace App\Providers;

use App\Events\ClientCodeUsed;
use App\Events\FailedPayment;
use App\Events\Payment;
use App\Events\UserStatusUpdatedEvent;
use App\Listeners\NotifyAdminsAboutClientCodeUsage;
use App\Listeners\NotifyAdminsTransactionSuccess;
use App\Listeners\NotifyBuyerTransactionFailed;
use App\Listeners\NotifyBuyerTransactionSuccess;
use App\Listeners\NotifyCustomerAboutClientCodeUsage;
use App\Listeners\SendUserStatusNotification;
use App\Listeners\UpdateClientCodeUsage;
use App\Listeners\UseCoupon;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Observers\OrderObserver;
use App\Observers\SettingObserver;
use App\Observers\UserObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;


class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Setting::observe(SettingObserver::class);
        Order::observe(OrderObserver::class);
        User::observe(UserObserver::class);
    }
}
