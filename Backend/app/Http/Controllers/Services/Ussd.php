<?php

namespace App\Http\Controllers\Service;


use App\Http\Controllers\Service\General;
use App\Http\Controllers\Service\ServiceRender;

use App\Packages;
use App\SubServices;
use App\Transaction;

use GuzzleHttp\Client;

class Ussd
{
    public static $base_uri = 'https://ussd.simhosting.ng/api/';

    public static function initiateService($service_type, $ussd_code, $data)
    {

        if ($service_type == 'DATA_PURCHASE') {

            self::purchaseData($ussd_code, $data);

        }
    }

    public static function connection()
    {
        return $client = new Client([
            'base_url' => '127.0.0.1',
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    public static function purchaseData($ussd_plug_code, $data)
    {


        $sim_service = explode(',', $ussd_plug_code);

        switch ($sim_service[1]) {
            case 'ussd':
                self::codeUssd($ussd_plug_code, $data);
                break;
            default:
                self::smsUssd($ussd_plug_code, $data);
        }

    }

    public static function smsUssd($ussd_plug_code, $data)
    {
        try {
            $sim_service = explode(',', $ussd_plug_code);

            $ussd_message = str_replace('[phone_number]', $data['phone_number'], $sim_service[0]);

            $fetch_package = Packages::where('pid', $data['package_id'])->first();

            $fetch_token_server_code = SubServices::where('sid', $fetch_package->sid)->first();

            $server_token_code_get = explode(':', $fetch_token_server_code->medium);

            $server_token_code = explode(',', $server_token_code_get[1]);

            $payload = [
                'ussd' => $sim_service[1],
                'message' => $ussd_message,
                'token' => $server_token_code[0],
                'servercode' => $server_token_code[1],
                'refid' => $data['reference']
            ];

            $params = http_build_query($payload);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$base_uri . 'shortcode/?');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result, true);

            if ($response['success'] == 'true') {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['log_id']
                ]);

            } else {

                General::logActivities('system', $response['comment'] . ' ussd api');

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'failed'
                ]);

                ServiceRender::refundUser($data['reference']);

            }

        } catch (\Exception $e) {

            General::logActivities('system', $e->getMessage() . ' ussd api');

            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }
    }

    public static function codeUssd($ussd_plug_code, $data)
    {
        try {
            $sim_service = explode(',', $ussd_plug_code);

            $ussd_code = str_replace('[phone_number]', $data['phone_number'], $sim_service[0]);

            $fetch_package = Packages::where('pid', $data['package_id'])->first();

            $fetch_token_server_code = SubServices::where('sid', $fetch_package->sid)->first();

            $server_token_code_get = explode(':', $fetch_token_server_code->medium);

            $server_token_code = explode(',', $server_token_code_get[1]);

            $payload = [
                'ussd' => $ussd_code,
                'token' => $server_token_code[0],
                'servercode' => $server_token_code[1],
                'refid' => $data['reference']
            ];

            $params = http_build_query($payload);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::$base_uri . 'ussd/?');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            $result = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($result, true);

            if ($response['success'] == 'true') {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['log_id']
                ]);

            } else {

                General::logActivities('system', $response['comment'] . ' ussd api');

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

    public static function verifyTransaction($transaction)
    {

        $fetch_package = Packages::where('pid', $transaction['packages_id'])->first();

        $fetch_token_server_code = SubServices::where('sid', $transaction->sub_service_id)->first();

        $server_token_code_get = explode(':', $fetch_token_server_code->medium);

        $server_token_code = explode(',', $server_token_code_get[1]);

        $status_array = array(
            "token" => $server_token_code[0],
            "log_id" => $transaction->payment_reference
        );
        $status_url = self::$base_uri . 'status/?' . http_build_query($status_array);

        $data = json_decode(file_get_contents($status_url), true);

        return json_encode(['message' => $data['data']['response']]);
    }


}
