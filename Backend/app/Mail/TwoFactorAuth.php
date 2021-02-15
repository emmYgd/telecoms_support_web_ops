<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorAuth extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    public $email;

    /**
     * Create a new message instance.
     *
     * @param $code
     * @param $email
     */
    public function __construct($code, $email)
    {
        $this->code = $code;

        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            return $this->from('no-reply@anniejacobsonline.com')->subject('Two Factor Verification')->view('mails.twofactor');
        } catch (\Exception $e) {
        }
    }
}
