<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Service\ServiceRender;

use App\SubServices;
use App\Transaction;

use GuzzleHttp\Client;

class SmePlug
{
    public static $base_uri = 'https://smeplug.ng/api/v1/';

    public static $authorization_token = 'bacfbb44196339bb0c9989e813c864fcc54d9db0f9202e7ba5e18711816f1384';

    public static function initiateService($service_type, $smeplug_code, $data)
    {

        if ($service_type == 'AIRTIME_PURCHASE') {

            self::purchaseAirtime($smeplug_code, $data);

        }

        if ($service_type == 'DATA_PURCHASE') {

            self::purchaseData($smeplug_code, $data);

        }
    }

    public static function connection()
    {
        return $client = new Client([
            'base_url' => '127.0.0.1',
            'headers' => [
                'Authorization' => 'Bearer ' . self::$authorization_token,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public static function purchaseAirtime($sme_plug_code, $data)
    {
        try {
            $ringo_purchase_code = explode(',', $sme_plug_code);

            $payload = [
                'network_id' => $ringo_purchase_code[0],
                'phone' => $data['phone_number'],
                'amount' => $data['amount']
            ];

            $request = self::connection()->request(
                'POST',
                self::$base_uri . 'airtime/purchase',
                [
                    'json' => $payload
                ]
            );

            $response = json_decode($request->getBody()->getContents(), true);

            if ($response['status']) {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['data']['reference']
                ]);

            } else {
                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'failed'
                ]);

                ServiceRender::refundUser($data['reference']);

            }

        } catch (\Exception $e) {

            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }

    }

    public function purchaseData($sme_plug_code, $data)
    {
        try {
            $ringo_purchase_code = explode(',', $sme_plug_code);

            $payload = [
                'network_id' => $ringo_purchase_code[0],
                'phone' => $data['phone_number'],
                'plan_id' => $ringo_purchase_code[1]
            ];

            $request = self::connection()->request(
                'POST',
                self::$base_uri . 'data/purchase',
                [
                    'json' => $payload
                ]
            );

            $response = json_decode($request->getBody()->getContents(), true);

            if ($response['status']) {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['data']['reference']
                ]);

            } else {
                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'failed'
                ]);

                ServiceRender::refundUser($data['reference']);

            }

        } catch (\Exception $e) {

            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }
    }

    public static function fetchExternalDataPlan()
    {
        $request = self::connection()->request(
            'GET',
            self::$base_uri . 'data/plans'
        );

        $result = json_decode($request->getBody()->getContents());

        return $request;
    }

    public static function initService($data)
    {

        return [
            'validate' => true,
            'sub_service_details' => SubServices::where('sid', $data['sub_service_id'])->first(),
            'amount_charge' => $data['amount']
        ];
    }
}
