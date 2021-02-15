<?php

namespace App\Mail;

use App\Transaction;
use App\User;
use App\Wallet;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FundingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $wallet_id;

    public $amount;

    public $wallet_details;

    public $funding_type;

    public $description;

    public $transaction_history;

    public $user;

    public $current_transaction;

    /**
     * Create a new message instance.
     *
     * @param $wallet_id
     * @param $amount
     * @param $funding_type
     * @param $description
     * @param $reference
     */
    public function __construct($wallet_id, $amount, $funding_type, $description, $reference)
    {

        $this->amount = $amount;

        $this->wallet_id = $wallet_id;

        $this->wallet_details = Wallet::where(['wallet_id' => $wallet_id])->first();

        $this->description = $description;

        $this->user = User::where('wallet_id', $this->wallet_details['wallet_id'])->first();

        $this->current_transaction = '';

        if ($reference !== '') {
            $this->current_transaction = Transaction::where('reference', $reference)->first();
        }

        $this->transaction_history = Transaction::where('uid', $this->user['id'])->where('reference', '!=', $reference)->orderBy('created_at', 'DESC')->take(6)->skip(1)->get();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'NaijaSub Transaction Alert (N' . number_format($this->amount) . ')';
        return $this->from('support@naijasub.com')->subject($subject)->view('mails.funding');
    }
}
