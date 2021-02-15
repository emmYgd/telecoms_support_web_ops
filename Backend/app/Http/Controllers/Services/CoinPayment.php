<?php

namespace App\Http\Controllers\Service;


use App\Http\Controllers\Service\CoinPaymentsAPI;
use App\Http\Controllers\Service\ServiceRender;

use App\Setting;
use App\Transaction;
use App\User;

use GuzzleHttp\Client;

class CoinPayment
{
    public static $secret_key = '900B791ca1Ce74f506BAe3D5e1a253c00376144299c0cFF856792a9c12F3396f';

    public static $public_key = '7dcedd378750aec396c51ce6c0037dcc0b8f389b8bd09c728f7b5673618f5d88';

    public static function access()
    {

        $coinpayment_api = new CoinPaymentsAPI();
        $coinpayment_api->Setup(self::$secret_key, self::$public_key);

        return $coinpayment_api;
    }

    public static function initiateService($service_type, $coin_code, $data)
    {

//        if ($service_type == 'PURCHASE_COIN') {
//            self::purchaseCoin($coin_code, $data);
//        }
//
        switch ($service_type) {
            case 'COIN_SELL':
                self::sellCoin($data);
                break;
            case 'COIN_PURCHASE':
                self::purchaseCoin($data);
                break;
        }

    }

    public static function purchaseCoin($data)
    {
        $response = self::access()->CreateWithdrawal($data['amount'], 'BTC', $data['wallet_address']);

        if ($response['error'] == 'ok') {
            Transaction::where('reference', $data['reference'])->update([
                'status' => 'success',
                'payment_reference' => $response['result']['id'],
                'coin_details' => json_encode($response['result'])
            ]);
        } else {
            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }
    }

    public static function sellCoin($data)
    {
        $payload = array(
            'amount' => $data['amount'],
            'currency1' => 'USD',
            'currency2' => 'btc',
            'buyer' => $data['email'],
            'item_name' => User::where('id', $data['uid'])->first()['name'/*first_name*/] . ' Sell BTC ',
            'address' => '',
            'ipn_url' => env('APP_URL') . 'api/confirm-coin'
        );

        $response = self::access()->CreateTransaction($payload);

        if ($response['error'] == 'ok') {
            Transaction::where('reference', $data['reference'])->update([
                'status' => 'success',
                'payment_reference' => $response['result']['txn_id'],
                'coin_details' => json_encode($response['result'])
            ]);

        } else {
            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }

    }

    public static function fetchCoinLiveRate()
    {
        $coin = self::access();

        return $coin->GetRates();
    }

    public static function convertCash($amount, $coin_type)
    {
        // fetch coin rate from db
        $setting = Setting::find(1);

        $conversation_rate = $setting->coin_rate;

        // expected format  is btc:rate,eth:rate convert to array
        $convert_conversation_rate = explode(',', $conversation_rate);

        $current_coin = '';

        //loop through convert conversation rate
        for ($i = 0; $i < count($convert_conversation_rate); $i++) {

            // check which conversation rate contain the coin type request
            if (strpos($convert_conversation_rate[$i], $coin_type)) {
                $current_coin = $convert_conversation_rate[$i];
                return;
            }

        }

        $convert_rate = explode(':', $current_coin);

        // return expected amount
        return $amount / $convert_rate[1];
    }

    public static function liveBTCRate($amount)
    {

        $client = new Client([
            'base_url' => '127.0.0.1:8000',
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        $request = $client->get('https://blockchain.info/tobtc?currency=USD&cors=false&value=' . $amount);

        return $request->getBody()->getContents();

    }


}
