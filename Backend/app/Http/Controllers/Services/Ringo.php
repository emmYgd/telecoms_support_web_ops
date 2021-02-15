<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Service\ServiceRender;

use App\Events\Notifications;
use App\MembershipPlan;
use App\Packages;
use App\SubServices;
use App\Transaction;

use GuzzleHttp\Client;
use Illuminate\Support\Str;


class Ringo
{
    public static $ringo_email = 'oshegztelecoms@gmail.com';

    public static $ringo_password = '@nsub1';

    public static $base_url = 'https://www.api.ringo.ng/api/agent/p2';

    public static $service_code_data = 'ADA';

    public static function initService($data)
    {

        $service_code = '';

        if ($data['service_type'] == 'trade airtime') {

            $service_code = 'VDA';

        } else if ($data['service_type'] == 'cable') {

            $service_code = 'V-TV';

        } else if (Str::lower($data['service_type']) == 'data') {
            $service_code = 'VDA';
        } else if (Str::lower($data['service_type']) == 'internet_service') {
            $service_code = 'V-Internet';
        } else if (Str::lower($data['service_type']) == 'electricity') {

            $service_code = 'V-ELECT';
        }

        return self::verifyService($service_code, $data['number'], $data);

    }

    /**
     * @param $service_type
     * @param $ringo_code
     * @param $data
     */
    public static function initiateService($service_type, $ringo_code, $data)
    {
        switch ($service_type) {
            case 'AIRTIME_PURCHASE':
                self::purchaseAirtime($ringo_code, $data);
                break;
            case 'DATA_PURCHASE':
                self::purchaseData($ringo_code, $data);
                break;
            case 'CABLE_PURCHASE':
                self::purchaseCable($ringo_code, $data);
                break;
            case 'ELECTRICITY_PURCHASE':
                self::purchaseElectricity($ringo_code, $data);
                break;
            case 'INTERNET_PURCHASE':
                self::purchaseData($ringo_code, $data);
                break;
        }

    }

    public static function connection()
    {
        return $client = new Client([
            'base_url' => '127.0.0.1:8000',
            'headers' => [
                'email' => self::$ringo_email,
                'password' => self::$ringo_password,
                'Content-Type' => 'application/json'
            ]
        ]);
    }

    // this is to verify service before proceeding with transaction
    public static function verifyService($service_code, $number, $data)
    {

        $payload = [
            'serviceCode' => $service_code,
            'msisdn' => $number,
            'amount' => $data['amount']
        ];
        
        if($data['service_type'] == 'airtime'){
          $payload = [
            'serviceCode' => 'VAR',
            'msisdn' => '0'.$number,
            'amount' => $data['amount'],
            'request_id' => time()
          ];  
        }

        if ($data['service_type'] == 'cable') {
            // service name
            $service_name = SubServices::where('sid', $data['sub_service_id'])->first()['name'];
            $payload = [
                'serviceCode' => $service_code,
                'smartCardNo' => $number,
                'type' => Str::upper($service_name)
            ];
            
        }
        
          if ($data['service_type'] == 'internet_service') {
            // service name
            $service_name = SubServices::where('sid', $data['sub_service_id'])->first()['name'];
            $payload = [
                'serviceCode' => 'V-Internet',
                'account' => $number,
                'type' => Str::upper($service_name)
            ];
            
        }
        
        

        if ($data['service_type'] == 'electricity') {
            // service name
            $service_name = SubServices::where('sid', $data['sub_service_id'])->first()['name'];
            $payload = [
                'serviceCode' => $service_code,
                'disco' => $data['medium_split'][0],
                'meterNo' => $number,
                'type' => $data['electricity_type']
            ];
        }
        
    var_dump($payload);

        $request = self::connection()->request(
            'POST',
            self::$base_url,
            [
                'json' => $payload
            ]
        );

        $response = json_decode($request->getBody()->getContents(), true);
        
        var_dump($response);

        if ($data['service_type'] == 'cable' || $data['service_type'] == 'electricity') {
            $response['message'] = 'SUCCESSFUL';
            $response['status'] = 200;

        }
           if ($data['service_type'] == 'internet_service' && $response['message'] == 'Successful') {
            $response['message'] = 'SUCCESSFUL';
            $response['status'] = 200;

        }

        if ($response['message'] == 'SUCCESSFUL' && $response['status'] == 200) {

            $extra_data = [];

            $discount_amount = 0;

            $membership_plane = 0;

            if ($data['service_type'] == 'airtime') {

                $extra_data = ['product_id_api' => $response['data']['products'][0]['product_id']];

                $membership_plane = MembershipPlan::where('name', $data['membership_level'])->first()['airtime_discount'];

                // calculate %percentage
                $membership_place_percentage_discount = ($membership_plane / 100) * $data['amount'];

                $discount_amount = $membership_place_percentage_discount;

            }

            if ($data['service_type'] == 'data') {

                $membership_plane = MembershipPlan::where('name', $data['membership_level'])->first()['data_discount'];

                // calculate %percentage
                $membership_place_percentage_discount = ($membership_plane / 100) * $data['amount'];

                $discount_amount = $membership_place_percentage_discount;

            }

            if ($data['service_type'] == 'cable') {

                $membership_plane = MembershipPlan::where('name', $data['membership_level'])->first()['cable_discount'];

                // calculate %percentage
                $membership_place_percentage_discount = ($membership_plane / 100) * $data['amount'];

                $discount_amount = $membership_place_percentage_discount;

            }

            if ($data['service_type'] == 'electricity') {

                $extra_data = ['customer_name' => $response['customerName'],'customer_address'=> $response['customerAddress']];
                $membership_plane = MembershipPlan::where('name', $data['membership_level'])->first()['electricity_discount'];

                // calculate %percentage
                $membership_place_percentage_discount = ($membership_plane / 100) * $data['amount'];

                $discount_amount = $membership_place_percentage_discount;
            }
            
            if($data['service_type'] == 'internet_service'){
                $extra_data = ['customer_name' => $response['customerName']];
                $membership_plane = 0;

                // calculate %percentage
                $membership_place_percentage_discount = ($membership_plane / 100) * $data['amount'];

                $discount_amount = $membership_place_percentage_discount;
            }

            return [
                'validate' => true,
                'sub_service_details' => SubServices::where('sid', $data['sub_service_id'])->first(),
                'amount_charge' => $data['amount'] - $discount_amount,
                'discount' => $membership_plane,
                'extra_data' => $extra_data
            ];
        }

        return [
            'validate' => false
        ];
    }

    public static function purchaseAirtime($ringo_code, $data)
    {
        try {
            // ringo code is separated by , for service requiring more than one code to purchase

            $ringo_purchase_code = explode(',', $ringo_code);

            $payload = [
                'serviceCode' => $ringo_purchase_code[0],
                'msisdn' => '0'.$data['phone_number'],
                'request_id' => $data['reference'],
                'product_id' => $data['product_code'],
                'amount' => $data['amount']
            ];


            $request = self::connection()->request(
                'POST',
                self::$base_url,
                [
                    'json' => $payload
                ]
            );

            $response = json_decode($request->getBody()->getContents(), true);


            if ($response['status'] == 200 && $response['message'] == 'SUCCESSFUL') {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['TransRef']
                ]);

            } else {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'failed'
                ]);

                ServiceRender::refundUser($data['reference']);

            }
        } catch (\Exception $e) {
            var_dump($e->getMessage() . '-' . $e->getFile() . $e->getCode());
        }

    }

    public static function purchaseData($ringo_code, $data)
    {
        // ringo code is separated by , for service requiring more than one code to purchase

        /** @var TYPE_NAME $ringo_purchase_code */
        $ringo_purchase_code = explode(',', $ringo_code);

        // data ringo:product-id,serviceCode or ringo:code,serviceCode,name,allowance
        $payload = [
            'serviceCode' => $ringo_purchase_code[1],
            'msisdn' => $data['phone_number'],
            'request_id' => $data['reference'],
            'product_id' => $ringo_purchase_code[0],
        ];


        if ($data['name_package'] == 'smile') {
            $payload = [
                "serviceCode" => "P-Internet",
                "type" => "SMILE",
                "price" => $data['amount'],
                "name" => $ringo_purchase_code[2],
                "allowance" => $ringo_purchase_code[3],
                "validity" => "1",
                "code" => $ringo_purchase_code[0],
                "account" => $data['phone_number'],
                "request_id" => $data['reference']
            ];
        }

        if ($data['name_package'] == 'spectranet') {
            $payload = [
                "serviceCode" => "P-Internet",
                "amount" => $data['amount'],
                "type" => "SPECTRANET ",
                "pinNo" => "1",
                "request_id" => $data['reference']

            ];
        }


        $request = self::connection()->request(
            'POST',
            self::$base_url,
            [
                'json' => $payload
            ]
        );

        $response = json_decode($request->getBody()->getContents(), true);

        if ($response['status'] == 200 && $response['message'] == 'SUCCESSFUL') {

            Transaction::where('reference', $data['reference'])->update([
                'status' => 'success',
                'payment_reference' => $response['TransRef']
            ]);

            if ($data['name_package'] == 'spectranet') {
                Transaction::where('reference', $data['reference'])->update([
                    'spectrant_data' => json_encode($response['pin'])
                ]);

            }

        } else {

            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);

        }
    }

    public static function purchaseCable($ringo_code, $data)
    {

        try {

            // ringo code is separated by , for service requiring more than one code to purchase

            $ringo_purchase_code = explode(',', $ringo_code);

            // dstv and gotv ringo:code,serviceCode,type,name
            $payload = [
                'serviceCode' => $ringo_purchase_code[1],
                'type' => $ringo_purchase_code[2],
                'smartCardNo' => $data['smart_card_number'],
                'name' => $ringo_purchase_code[3],
                'code' => $ringo_purchase_code[0],
                'period' => '1',
                'request_id' => $data['reference'],
                'amount' => $data['amount']
            ];

            if ($ringo_purchase_code[2] == 'STARTIMES') {
                $payload = [
                    "serviceCode" => "P-TV",
                    "type" => "STARTIMES",
                    "smartCardNo" => $data['smart_card_number'],
                    "request_id" => $data['reference'],
                    "price" => $data['amount']
                ];
            }

            $request = self::connection()->request(
                'POST',
                self::$base_url,
                [
                    'json' => $payload
                ]
            );

            $response = json_decode($request->getBody()->getContents(), true);

            if ($response['status'] == 200 && $response['message'] == 'SUCCESSFUL') {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['TransRef']
                ]);

            } else {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'failed'
                ]);

                ServiceRender::refundUser($data['reference']);

            }

        } catch (\Exception $exception) {

            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }

    }

    public static function purchaseElectricity($ringo_code, $data)
    {

        try {

            // ringo code is separated by , for service requiring more than one code to purchase
            $ringo_purchase_code = explode(',', $ringo_code);

            $payload = [
                'serviceCode' => $ringo_purchase_code[1],
                'disco' => $ringo_purchase_code[0],
                'meterNo' => $data['meter_number'],
                'type' => $ringo_purchase_code[2],
                'amount' => $data['amount'],
                'request_id' => $data['reference'],
                'phonenumber' => $data['phone_number']
            ];
            
            
            $request = self::connection()->request(
                'POST',
                self::$base_url,
                [
                    'json' => $payload
                ]
            );

            $response = json_decode($request->getBody()->getContents(), true);
            
        
            if ($response['status'] === '200' && $response['message'] === 'Successful') {

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'success',
                    'payment_reference' => $response['TransRef'],
                    'token' => $response['token'],
                    'units' => $response['unit']
                ]);

            } else {
        
        

                Transaction::where('reference', $data['reference'])->update([
                    'status' => 'failed'
                ]);

                ServiceRender::refundUser($data['reference']);

            }

        } catch (\Exception $exception) {


            Transaction::where('reference', $data['reference'])->update([
                'status' => 'failed'
            ]);

            ServiceRender::refundUser($data['reference']);
        }

    }

    public static function verifyTransaction($transaction_ref)
    {
        $payload = [
            'serviceCode' => 'RRC',
            'transRef' => $transaction_ref
        ];

        $request = self::connection()->request('POST', self::$base_url, [
            'json' => $payload
        ]);

        $result = Json_decode($request->getBody()->getContents(), true);

        return json_encode([
            'message' => $result['data']['status']
        ]);

    }

    public static function fetchExternalService($service_number, $type, $other_service_type)
    {

        switch ($type) {
            case 'data':
                return self::externalDataService($service_number, $other_service_type);
                break;
            case 'cable':
                return self::externalCableService($other_service_type);
                break;
            case 'electricity':
                return self::externalElectricityService();
                break;
            default:
                return [];
        }

    }

    public static function externalDataService($phone, $type)
    {
        $payload = [
            'serviceCode' => 'DTA',
            'msisdn' => $phone
        ];

        if ($type == 'SMILE') {
            $payload = [
                "serviceCode" => "V-Internet",
                "account" => "1402000567",
                "type" => "SMILE"
            ];
        }
        
        $request = self::connection()->request(
            'POST',
            self::$base_url,
            [
                'json' => $payload
            ]
        );

        return json_decode($request->getBody()->getContents(), true);
    }

    public static function externalCableService($service_type, $smart_card = '10441003943')
    {
        if ($service_type == 'dstv') {
            $smart_card = '10441003943';
        } else {
            $smart_card = '7017672430';
        }

        $payload = [
            'serviceCode' => 'V-TV',
            'smartCardNo' => $smart_card,
            "type" => Str::upper($service_type)
        ];
        
        

        $request = self::connection()->request(
            'POST',
            self::$base_url,
            [
                'json' => $payload
            ]
        );

        return json_decode($request->getBody()->getContents(), true);
    }

    public static function externalElectricityService()
    {

        $payload = [
            'serviceCode' => 'ELECT'
        ];

        $request = self::connection()->request(
            'POST',
            self::$base_url,
            [
                'json' => $payload
            ]
        );

        return json_decode($request->getBody()->getContents(), true);
    }

    public static function saveExternalService($service_type, $type, $data, $other_service)
    {
        switch ($type) {
            case 'data':
                return self::saveDataPackages($service_type, $type, $data);
                break;
            case 'cable':
                return self::saveExternalCableService($service_type, $type, $data, $other_service);
                break;
            default:
                return [];
        }
    }

    public static function saveDataPackages($service_type, $type, $data)
    {
        for ($i = 0; $i < count($data); $i++) {

            // check if packages already available

            // build initial medium        //
            $medium = $service_type . ':' . $data[$i]['details']['product_id'] . ',' . self::$service_code_data;

            // check if this medium already exit in db
            $medium_exist = Packages::where('medium', $medium)->first();

            if ($medium_exist) {
                Packages::where('pid', $medium_exist->pid)->update([
                    'amount' => $data[$i]['amount']
                ]);
            } else {
                Packages::create([
                    'name' => $data[$i]['details']['network'] . ' ' . $data[$i]['details']['allowance'],
                    'sid' => $data[$i]['service_type_id'],
                    'amount' => $data[$i]['amount'],
                    'medium' => $medium,
                    'status' => 'active'
                ]);
            }

        }

    }
    
    public static function saveExternalCableService($service_type, $type, $data, $other_service)
    {
        // dstv and gotv ringo:code,serviceCode,type,name
        for ($i = 0; $i < count($data); $i++) {

            // check if packages already available

            // build initial medium        //
            $medium = 'ringo' . ':' . $data[$i]['details']['code'] . ',' . 'P-TV,' . $other_service . ',' . $data[$i]['details']['name'];

            // check if this medium already exit in db
            $medium_exist = Packages::where('medium', $medium)->first();

            if ($medium_exist) {
                Packages::where('pid', $medium_exist->pid)->update([
                    'amount' => $data[$i]['amount']
                ]);
            } else {
                Packages::create([
                    'name' => $data[$i]['details']['name'],
                    'sid' => $data[$i]['service_type_id'],
                    'amount' => $data[$i]['amount'],
                    'medium' => $medium,
                    'status' => 'active'
                ]);
            }

        }
    }

}
