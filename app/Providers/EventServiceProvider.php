<?php

namespace App\Providers;

use App\Events\ClientCodeUsed;
use App\Events\FailedPayment;
use App\Events\Payment;
use App\Events\UserStatusUpdatedEvent;
use App\Listeners\NotifyAdminsAboutClientCodeUsage;
use App\Listeners\NotifyBuyerTransactionFailed;
use App\Listeners\NotifyBuyerTransactionSuccess;
use App\Listeners\NotifyCustomerAboutClientCodeUsage;
use App\Listeners\SendUserStatusNotification;
use App\Listeners\UpdateClientCodeUsage;
use App\Listeners\UseCoupon;
use App\Models\Setting;
use App\Observers\SettingObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Payment::class => [
            NotifyBuyerTransactionSuccess::class,
            UseCoupon::class,
        ],
        FailedPayment::class => [
            NotifyBuyerTransactionFailed::class,
        ],
        UserStatusUpdatedEvent::class => [
            SendUserStatusNotification::class,
        ],
        ClientCodeUsed::class => [
            UpdateClientCodeUsage::class,
            NotifyCustomerAboutClientCodeUsage::class,
            NotifyAdminsAboutClientCodeUsage::class,
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Setting::observe(SettingObserver::class);
    }
}
