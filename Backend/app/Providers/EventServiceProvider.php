<?php

namespace App\Providers;

use App\Events\Notifications;
use App\Events\ReferralUpgrade;
use App\Events\TransactionEvents;
use App\Events\UserDownLine;

use App\Listeners\NotificationsServices;
use App\Listeners\ReferralUpgradeService;
use App\Listeners\TransactionEventsService;
use App\Listeners\UserDownLineServices;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Notifications::class => [
            NotificationsServices::class
        ],
        UserDownLine::class => [
            UserDownLineServices::class
        ],
        ReferralUpgrade::class => [
            ReferralUpgradeService::class
        ],
        TransactionEvents::class => [
            TransactionEventsService::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
