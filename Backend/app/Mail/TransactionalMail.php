<?php

namespace App\Mail;

use App\Transaction;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionalMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $created_at;
    public $service_type;
    public $data;

    /**
     * Create a new message instance.
     *
     * @param $transaction_type
     * @param $data
     * @param $email
     */
    public function __construct($service_type, array $data)
    {
        //set class variables:
        $this->service_type = $service_type;
        $this->data = $data;
        $this->created_at = Transaction::where(['uid' => $data->uid])->first()['updated_at']; 
    }

    /**
     * Build the message.
     *
     * @return $this
     */
     
    public function build()
    {
        
        //return $this->view('view.name');
        if(isset($data)){
            $subject = 'NaijaSub Transaction Alert (N' . number_format($data->amount) . ')';
            return $this->from('support@naijasub.com')->subject($subject)->view('mails.transaction');
        }
    }
}
