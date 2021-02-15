<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;

    public $name;

    public $verification_url;

    /**
     * Create a new message instance.
     *
     * @param $name
     * @param $token
     */
    public function __construct($name, $token)
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
        try {
            return $this->from('support@naijasub.com')->subject('Activation Mail')->view('mails.activation');
        } catch (\Exception $e) {
        }
    }
}
