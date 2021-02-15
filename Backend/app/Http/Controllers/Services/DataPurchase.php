<?php

namespace App\Http\Controllers\Service;

use GuzzleHttp\Client;

class DataPurchase
{

    //smeify:
    public $smeifyAuthEndpoint = "https://auto.smeify.com/api/v1/auth/login";
    
    public $smeifyRefreshEndpoint = " https://auto.smeify.com/api/v1/refresh";
    
    public $smeifyDataEndpoint = "https://auto.smeify.com/api/v1/online/data";
    
    public $smeifyID = "oshegztelecoms@gmail.com";
    
    public $smeifyPassword = "oshegz12345";
    
    
    //bulk sms:
    public $bulkSmsEndpoint = "https://www.bulksmsnigeria.com/api/v1/sms/create";
    
    public $bulk_sms_nigeria_api_Key = "uvGzd3n8wKYtlwiwbO0SPamxedA3V52aTBYkh9lriCIeB8saxirQp85LLOzr" ;
    
    public $adminSenderId = "NaijaSub";
    
    //client making the recharge request:
    public $client = new Client([
        'base_uri' => 'https://api.naijasub.com/'
    ]);
    
    public static function buyDataBulkSms(array $payload){
        
        //first use bulk sms API:
        $request = $this->client->request('GET', $this->bulkSmsEndpoint, [
            'json' => $payload
        ]);
                    
        $expectedResponse = $request->getBody()->getContents();
                    
        $processedResponse = json_decode($expectedResponse, TRUE);
                    
        if( isset($processedResponse) && ($processedResponse->status == "success") ){
                        
            return TRUE;
                        
        }
                    
    }
     
     
    public static function buyDataSmeify(array $dataPayload){
    
        
        $authPayload = [
            'identity' => $this->smeifyID,
            'password' => $this->smeifyPassword
        ];
        
        //first authenticate with the smeify endpoint
        $authRequest = $this->client->request('GET', $this->smeifyAuthEndpoint, [
            'json' => $authPayload
        ]);

        $expectedAuthResponse = $authRequest->getBody()->getContents();
                    
        $processedAuthResponse = json_decode($expectedAuthResponse, TRUE);

        if( ($processedAuthResponse->code == 200) && !empty($processedAuthResponse->token) ){

            //now get the token from the obtained response:
            $authToken = $processedAuthResponse->token;
            
            //then request with the obtained token:
             $dataRequest = $this->client->request('GET', $this->smeifyDataEndpoint, [

                'json' => $dataPayload, 

                'headers' => [ 'Authorization' => 'Bearer ' . $authToken]

            ]);

            $expectedDataResponse = $dataRequest->getBody()->getContents();

            $processedDataResponse = json_decode($expectedDataResponse, TRUE);

            if($processedDataResponse->code == 200){

                //data purchase successful:
                return TRUE;

            }
        }
            
    }
    
}   
