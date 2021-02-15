<?php

namespace App\Http\Controllers\Service;

use App\Events\TransactionEvents;

use App\Http\Controllers\Service\Funding;
use App\Http\Controllers\Service\General;
use App\Http\Controllers\Service\Ringo;
use App\Http\Controllers\Service\SmePlug;
use App\Http\Controllers\Service\Ussd;
use App\Http\Controllers\Service\DataPurchase;

use App\MembershipPlan;
use App\Packages;
use App\SubServices;
use App\Transaction;
use App\User;
use App\Admin;

use GuzzleHttp\Client;

class ServiceRender
{
    // expected medium ringo, smeplug, ussd
    
    

    public static function initTransaction($type, array $data)
    {

        switch ($type) {
            case 'ringo':
                return Ringo::initService($data);
                break;
            default:
                General::logActivities('system', json_encode($data) . ':Service was called but no service type present');
        }
    }

    /**
     * @param $type
     * @param array $data
     * @throws \Exception
     */
    public static function initiateTransaction($type, array $data)
    {
        switch ($type) {
            
            case 'AIRTIME_PURCHASE':

                // fetch sub_service details
                $service_details = SubServices::where('sid', $data['sid'])->first();

                // check for third party api to be used
                $third_party_api = explode(':', $service_details->medium);

                event(new TransactionEvents('AIRTIME_PURCHASE', $third_party_api[0], $third_party_api[1], $data));

                break;
                
            case 'DATA_PURCHASE'://investigate this later...
                
                // fetch package details
                //$package_details = Packages::where('pid', $data['package_id'])->first();

                // input the service name at this point
                //$data['name_package'] = SubServices::where('sid', $package_details['sid'])->first()['display_name'];

                // check for third party api to be used
                //$third_party_api = explode(':', $package_details->medium);
                
                if($data['network'] == 'MTN'){
                    
                    //set to NTN to avoid violation:
                    $data['network'] = 'NTN';
                }
                
                //fires an event: check this out later...(trace it)
                //event(new TransactionEvents('DATA_PURCHASE', $third_party_api[0], $third_party_api[1], $data));

                $dataReqMessageBody = "{$data['network']}|{$data['data_volume']}|{$data['phone_number']}";
                
                //check admin's choice for vendors:
                $adminDataPurchaseVendorChoice =  Admin::first()['adminVendorChoice'];
                
                if( $adminDataPurchaseVendorChoice == "bulk_sms_nigeria"){
                    
                    //construct a payload here:
                    $payload = [
                        'api_token' => $this->bulk_sms_nigeria_api_Key,
                        'from' => $this->adminSenderId,
                        'to' => $data['adminPhoneNumber'],//get admin phone number from db...
                        'body' => $dataReqMessageBody
                    ];
                
                    $purchaseSuccess = DataPurchase::buyDataBulkSms($payload);
                    if($purchaseSuccess){
                        
                        return TRUE;
                        
                    }else{
                        //this smeify API used is a fallback - 
                        //dataPayload:
                        $dataPayload = [
                            'network' => $data['network'],
                            'phone' => $data['phone_number'],
                            'volume' => $data['data_volume']
                            //'plan' =>
                        ];
                        
                        $purchaseSuccess = DataPurchase::buyDataSmeify($dataPayload);
                        if($purchaseSuccess){
                            
                            return TRUE;
                            
                        }else{
                            
                            return FALSE; 
                            
                        }
                    }
                    
                }else if( $adminDataPurchaseVendorChoice == "smeify"){

                    //use smeify API - 
                    //dataPayload:
                    $dataPayload = [
                        'network' => $data['network'],
                        'phone' => $data['phone_number'],
                        'volume' => $data['data_volume']
                        //'plan' =>
                    ];
                        
                    $purchaseSuccess = DataPurchase::buyDataSmeify($dataPayload);
                    if($purchaseSuccess){
                            
                        return TRUE;
                            
                    }else{
                            
                        return FALSE; 
                            
                    }
                    
                }

                break;
                
            case 'INTERNET_PURCHASE'://investigate this later...

                // fetch package details
                $package_details = Packages::where('pid', $data['package_id'])->first();

                // input the service name at this point
                $data['name_package'] = SubServices::where('sid', $package_details['sid'])->first()['display_name'];

                // check for third party api to be used
                $third_party_api = explode(':', $package_details->medium);

                event(new TransactionEvents('INTERNET_PURCHASE', $third_party_api[0], $third_party_api[1], $data));

                break;

            case 'CABLE_PURCHASE':
                // fetch package details
                $package_details = Packages::where('pid', $data['package_id'])->first();

                // check for third party api to be used
                $third_party_api = explode(':', $package_details->medium);

                event(new TransactionEvents('CABLE_PURCHASE', $third_party_api[0], $third_party_api[1], $data));
                
                break;
                
            case 'ELECTRICITY_PURCHASE':
                
                // fetch package details
                $package_details = SubServices::where('sid', $data['service_id'])->first();

                // check for third party api to be used
                $third_party_api = explode(':', $package_details->medium);

                event(new TransactionEvents('ELECTRICITY_PURCHASE', $third_party_api[0], $third_party_api[1], $data));
                
                break;
                
                /*$data = [
                    'amount' => $amount,
                    'uid' => $user->id,
                    'reference' => $reference,
                    'channel' => 'SELL_COIN',
                    'email' => $user['email']
                ];*/
                
            case 'COIN_PURCHASE':
                
                // fetch package details
                //$third_party_api = 'coin';
                event(new TransactionEvents('COIN_PURCHASE', 'coin', 'buy'/*$third_party_api[1]*/, $data));
                
                break;
                
            case 'COIN_SELL':
                
                //$third_party_api = 'coin';
                event(new TransactionEvents('COIN_SELL', 'coin', 'sell', $data));
                
                break;
                
            default:
                throw new \Exception('Invalid service option provided');

        }


    }

    public static function reQuery($transaction)
    {
        // check service medium
        $service_medium = '';

        if ($transaction->packages_id) {
            $pick_medium = Packages::where('pid', $transaction->packages_id)->first();
            $service_medium = self::splitServiceMedium($pick_medium->medium);
        } else {
            $pick_medium = SubServices::where('sid', $transaction->sub_service_id)->first();
            $service_medium = self::splitServiceMedium($pick_medium->medium);
        }

        switch ($service_medium) {

            case 'ringo':
                if (!$transaction->payment_reference)
                    throw new \Exception('Transaction failed before getting to service provider');

                return Ringo::verifyTransaction($transaction->payment_reference);
                break;
            case 'smeplug':
                return json_encode([
                    'message' => 'No verification details'
                ]);
                break;
            case 'ussd':
                if (!$transaction->payment_reference)
                    throw new \Exception('Transaction failed before getting to service provider');

                return Ussd::verifyTransaction($transaction);
                break;
            case 'coin';

                break;
            default:


        }
    }

    public static function splitServiceMedium($service_medium)
    {
        // split medium to return the service
        $medium = explode(':', $service_medium);

        return $medium[0];
    }

    public static function refundUser($reference)
    {
        // refund is based
        $transaction = Transaction::where(['reference' => $reference])->first();

        if (!$transaction) {
            return;
        }

        // credit wallet
        $fund = new Funding();
        $fund->creditWallet($transaction->uid, $transaction->amount);

        $sub_service = '';

        $packages = '';

        if ($transaction->transaction_type == 'coin') {
            $coin_service_type = json_decode($transaction->coin_details, true);

            if ($coin_service_type['type'] == 'buy')
                $description = 'Coin Purchase with transaction id ' . $reference . ' failed and amount has been refunded.';

            if ($coin_service_type['type'] == 'sell')
                $description = 'Coin sale with transaction id ' . $reference . ' failed and amount has been refunded.';
        }
        if ($transaction->sub_service_id)
            $sub_service = SubServices::where('sid', $transaction->sub_service_id)->first();

        if ($transaction->packages_id)
            $packages = Packages::where('pid', $transaction->packages_id)->first();

        if ($transaction->transaction_type !== 'coin')
            $service_name = (!empty($packages)) ? $packages->name : $sub_service->name;

        if ($transaction->transaction_type !== 'coin')
            $description = 'Purchase of ' . $service_name . ' failed and amount has been refunded.';

        General::logActivities($transaction->uid, $description);
    }

    public static function fetchPackages($service_type, $phone, $type, $other_service_type)
    {
        return Ringo::fetchExternalService($phone, $type, $other_service_type);

    }

    public static function saveServicePackages($type, $service_type, $data, $other_service)
    {

        switch ($service_type) {

            case 'ringo':

                Ringo::saveExternalService($service_type, $type, $data, $other_service);

                break;

        }

    }

    public static function serviceBonus($uid, $amount, $type)
    {

        $user = User::where('id', $uid)->first();

        $check = '';

        if ($type == 'airtime') {
            $check = 'airtime_discount';
        }

        $membership_plane = MembershipPlan::where('name', $user['membership_level'])->first()[$check];

        // calculate %percentage
        $membership_place_percentage_discount = ($membership_plane / 100) * $amount;

        $discount_amount = $membership_place_percentage_discount;

        return $amount - $discount_amount;
    }
}
