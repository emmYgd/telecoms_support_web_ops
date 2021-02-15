<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $token;

    public $name;

    public $verification_url;

    /**
     * Create a new message instance.
     *
     * @param $token
     * @param $name
     */
    public function __construct($token, $name)
    {
        $this->token = $token;

        $this->name = $name;

        $this->verification_url = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('support@naijasub.com')->view('mails.forgotpassword');
    }
}
