<?php

namespace App\Listeners;

use App\Events\TransactionEvents;

use App\Http\Controllers\Service\General;
use App\Http\Controllers\Service\CoinPayment;
use App\Http\Controllers\Service\Ringo;
use App\Http\Controllers\Service\SmePlug;
use App\Http\Controllers\Service\Ussd;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

use App\Mail\TransactionMail;

class TransactionEventsService
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
    public function handle(TransactionEvents $event)
    {   
        $data = $event->data;
        $user_email = $data['email'];
        
        switch ($event->channel_name) {
            
            case 'ringo':
                
                Ringo::initiateService($event->service_type, $event->channel_code, $event->data);
                
                //This is where the mail should be...
                Mail::to($user_email)->send(new TransactionalMail($event->service_type, $event->data));
                
                break;
                
            case 'smeplug':
                
                SmePlug::initiateService($event->service_type, $event->channel_code, $event->data);
                
                //This is where the mail should be...
                Mail::to($user_email)->send(new TransactionalMail($event->service_type, $event->data));
                
                break;
                
            case 'ussd':
                
                Ussd::initiateService($event->service_type, $event->channel_code, $event->data);
                
                //This is where the mail should be...
                Mail::to($user_email)->send(new TransactionalMail($event->service_type, $event->data));
                
                break;
                
            case 'coin':
                
                CoinPayment::initiateService($event->service_type, $event->channel_code, $event->data);
                
                //This is where the mail should be...
                Mail::to($user_email)->send(new TransactionalMail($event->service_type, $event->data));
                
                break;
                
            default:
                General::logActivities('system', json_encode($event->data) . ':Service was called but no service type present');
        }

    }
}
