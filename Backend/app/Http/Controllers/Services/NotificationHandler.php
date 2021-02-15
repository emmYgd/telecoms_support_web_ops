<?php

namespace App\Http\Controllers\Service;

//use App\Http\Controllers\Controller;
use App\Mail\FundingMail;

use App\Notification;
use App\User;

use Illuminate\Support\Facades\Mail;

class NotificationHandler //extends Controller
{
    /**
     * @param array $data
     */
    public static function debitMail(array $data)
    {
        $userDetails = User::where(['id' => $data['uid']])->first();
        
        $wallet_id = $userDetails->wallet_id;
        $amount = $data['amount'];
        $trans_type = 'debit';
        $description = $data['description'];
        $transaction = $data['transaction'];

        Mail::to($userDetails->email)->send(new FundingMail($wallet_id, $amount, $trans_type, $description, $transaction));

        if (isset($data['other']['transfer_option'])) {
            $data = array('uid' => $data['other']['receiver_id'], 'amount' => $data['amount'], 'description' => $data['other']['description']);
            self::creditMail($data);
        }
        
        //save notification:
        Notification::create([
            'uid' => $uid,
            'message' => $message,
            'medium' => 'in-app'
        ]);

    }

    /**
     * @param array $data
     */
    public static function creditMail(array $data)
    {
        $userDetails = User::where(['id' => $data['uid']])->first();
        
        $wallet_id = $userDetails->wallet_id;
        $amount = $data['amount'];
        $trans_type = 'credit';
        $description = $data['description'];
        $transaction = $data['transaction'];

        Mail::to($userDetails->email)->send(new FundingMail($wallet_id, $amount, $trans_type, $description, $transaction));
        
        Notification::create([
            'uid' => $uid,
            'message' => $message,
            'medium' => 'in-app'
        ]);
    }

    public static function saveNotification($uid, $message)
    {
        Notification::create([
            'uid' => $uid,
            'message' => $message,
            'medium' => 'in-app'
        ]);
    }

    public static function smsNotification($uid, $message)
    {
    }
}
