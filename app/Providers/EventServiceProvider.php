<?php

namespace App\Providers;

use App\Events\FailedPayment;
use App\Events\Payment;
use App\Listeners\NotifyBuyerTransactionFailed;
use App\Listeners\NotifyBuyerTransactionSuccess;
use App\Listeners\UseCoupon;
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
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}
