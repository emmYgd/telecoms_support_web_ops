<?php

namespace App\Listeners;

use App\Events\Notifications;

use App\Http\Controllers\Service\NotificationHandler;

class NotificationsServices
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param object $event
     * @return void
     */
    public function handle(Notifications $event)
    {
        // check notification type

        try {

            switch ($event->notification_type) {

                case 'debit':
                    NotificationHandler::debitMail($event->data);
                    break;
                case 'credit':
                    /** @var TYPE_NAME $event */
                    NotificationHandler::creditMail($event->data);
                    break;
            }

        } catch (\Exception $e) {
//            var_dump($e->getMessage().$e->getFile().$e->getLine());
        }

    }
}
