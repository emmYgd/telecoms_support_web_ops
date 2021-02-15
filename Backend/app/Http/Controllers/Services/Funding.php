<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Service\CoinPayment;

use App\Events\Notifications;

use App\Card;
use App\FundingCharge;
use App\PaymentLog;
use App\Transaction;
use App\User;
use App\Wallet;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

class Funding
{

    public $client;
    
    //get the Flutterwave Secret Key for  live account:
    public $secretKeyFlutterWave = 'FLWSECK-b6ec039dd8f8d910961758d344788e8a-X';
    
    public $pubKeyFlutterWave = 'FLWPUBK-86c446bd82d296e71c3b194fd33bb91b-X';

    public $secret_key = 'VA5LA6SYUEWL5XAHYVAP34K79KGLUCTB';

    public $api_key = 'MK_PROD_RREU6VSZQE';

    public $wallet_id_monify = '0E2DC25ACFB74F0C921D222458324343';

    public $contract_key = '424871199154';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.naijasub.com/'
        ]);
    }

    public function fundingCharges($type)
    {
        return $funding_charges = FundingCharge::where('type', $type)->first()['amount'];
    }

    public function fundingMethod($data)
    {
        $response = [
            'status' => 'failed', 
            'message' => 'No valid payment method selected'
        ];

        switch ($data['method']) {
            
            case 'flutter_card':
                $response = $this->flutterWaveFundingCard($data['card_id'], $data['amount']);
                break;
                
            case 'providus':
                $response = $this->providusApiFunding($data['uid'] /*$data['amount'] $data['other']['providus']*/);
                break;
                
            case 'transfer':
                if (empty($data['receiver_id'])){
                    return array(
                        'status' => 'failed', 
                        'message' => 'Receiver not provided'
                    );
                }else{
                    
                    $uid = $data['uid'];
                    $amount = $data['amount'];
                    //expecting either the wallet id or username here:
                    $receiver_wallet_id_or_username = $data['receiver_id'];
                    
                    $response = $this->transferFunding($amount, $receiver_wallet_id_or_username, $uid);
                }
                break;
                
            case 'flutter_verify':
                $response = $this->flutterWaveFundingVerify($data['tnx'], $data['amount'], $data['uid']);
                break;
                
            case 'manuel':
                $response = $this->manualFunding($data['amount'], $data['uid'], /*$data['other']['banks'],*/ $data['other']);
                break;
                
            case 'pay_airtime':
                $response = $this->airtimeFunding($data['amount'], $data['uid'], $data['other']);
                break;
                
            default:
                $response = ['status' => 'failed', 'message' => 'No valid payment method selected'];
        }

        return $response;
    }
    

    public function flutterWaveFundingVerify($tnx, $amount, $uid)
    {

        if (empty($tnx)){
            
            return array(
                'status' => 'failed', 
                'message' => 'Payment reference id is required'
            );
        }

        $reference = 'GF' . Str::random(6);

        Transaction::create([
            'reference' => $reference,
            'uid' => $uid,
            'amount' => $amount,
            'payment_reference' => $tnx,
            'description' => 'Funding of ' . $amount,
            'transaction_type' => 'gateway_funding',
            'status' => 'pending',
        ]);

        $data = array(

            'SECKEY' => $this->secretKeyFlutterWave,

            'txref' => $tnx
        );

        $request = $this->client->request('POST', 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify', [
            'json' => $data
        ]);

        $response = json_decode($request->getBody(), TRUE);

        if (($response['data']['chargecode'] == "00" || $response['data']['chargecode']) && ($response['data']['amount'] >= $amount)) {

            $card = [
                "expiryDate" => $response['data']['card']['expirymonth'] . '-' . $response['data']['card']['expiryyear'],
                "cardBin" => $response['data']['card']['cardBIN'],
                "last4digit" => $response['data']['card']['last4digits'],
                "brand" => $response['data']['card']['brand'],
                "cardToken" => $response['data']['card']['card_tokens'][0]['embedtoken'],
                "accountname" => $response['data']['custname']
            ];

            $this->saveCard($uid, $card);

            General::logActivities($uid, 'Funding account with ' . $amount . ' with gateway with payment id ' . $tnx);

            Transaction::where(['payment_reference' => $tnx, 'reference' => $reference])->update([
                'status' => 'success'
            ]);

            $this->creditWallet($uid, $amount, $reference);

            return array(
                'status' => 'success',
                'amount' => $response['data']['amount'],
                'message' => 'Account credited successfully'
            );

        } else {

            General::logActivities($uid, 'Funding account with ' . $amount . ' with gateway with payment id ' . $tnx . ' failed');

            Transaction::where(['payment_reference' => $tnx, 'reference' => $reference])->update([
                'status' => 'failed'
            ]);

            return array('status' => 'failed', 'message' => 'Funding failed verification');

        }

    }

    // duplicate but already in design verify payment:
    public function verifyPaymentFlutter($tnx, $amount)
    {

        $data = array(

            'SECKEY' => $this->secretKeyFlutterWave,

            'txref' => $tnx
        );


        $request = $this->client->request('POST', 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify', [
            'json' => $data
        ]);

        $response = json_decode($request->getBody(), TRUE);

        if ( ($response['data']['status'] === "success")&&($response['data']['chargecode'] == "00")
        ){
            if ($response['data']['amount'] >= $amount) {

                return array(
                    'status' => 'success',
                    'amount' => $response['data']['amount'],
                    'message' => 'Account credited successfully'
                );
            }
        } else {
            return array(
                'status' => 'failed', 
                'message' => 'Funding failed verification'
            );
        }

    }

    //$this->flutterWaveFundingCard($data['card_id'], $data['amount'])
    public function flutterWaveFundingCard($card_id, $amount)
    {

        if (empty($card_id)){
            return array(
                'status' => 'failed', 
                'message' => 'No card selected.'
            );
        }
        
        if(amount <= 0){
            return array(
                'status' => 'failed', 
                'message' => 'Amount must be greater than  zero.'
            );
        }
        
        // fetch card_details
        $card_details = Card::where(['id' => $card_id])->first();

        if (!$card_details){
            return array('status' => 'declined');
        }

        // fetch user details:
        $user_details = User::where(['id' => $card_details->uid])->first();

        $tnxid = Str::random(12);

        $reference = Str::random(12);
        
        //ensure unique transaction id and reference id:
        while (Transaction::where('payment_reference', $tnxid)->count() > 0) {
            $tnxid = Str::random(12);
        }

        while (Transaction::where('reference', $reference)->count() > 0) {
            $reference = Str::random(12);
        }

        Transaction::create([
            'reference' => $reference,
            'uid' => $card_details->uid,
            'amount' => $amount,
            'payment_reference' => $tnxid,
            'description' => 'Funding of ' . $amount,
            'transaction_type' => 'gateway_funding',
            'status' => 'pending',
        ]);
        
        //new request payload format:
        /*"tx_ref" => $tnxid,
            
            "amount" => $amount,
            
            "currency" => "NGN",
            
            "payment_options" => "card",
            
            "meta" => [
                "consumer_id" => $user_details->id
            ],
            
            "customer" => [
                'email' => $user_details->email,
                "phonenumber" => $user_details->phone,
                "name" => "{$user_details->first_name} {$user_details->last_name}"
            ],
            
            "customizations" => [
                "title" => "Service payment",
                "description" => "NaijaSub Wallet Funding through Card Payment. Powered by Flutterwave Payment Gateway API"
            ]*/

        $payload = array(
            
            "PBFPubKey" => $this->pubKeyFlutterWave,
            
            "txref" => $tnxid,
            
            "amount" => $amount,
            
            "payment_options" => "card",
            
            "currency" => "NGN",

            "country" => "NG",
            
            "customer_email" => "{$user_details->email}",
            
            "customer_phone" => "{$user_details->phone}",
            
            "customer_firstname" => "{$user_details->first_name}",
            
            "customer_lastname" => "{$user_details->last_name}",
            
            "pay_button_text" => "Fund Wallet with NGN{$amount}",
            
            "custom_title" => "NaijaSub Wallet Funding",
            
            "custom_description" => "Fund naijasub.com wallet through your debit card!",
            
            "redirect_url" => ""
            
            
            
            
            //'ip' => self::checkIpAddress(),
            
            //'token' => $card_details->card_token,

            //'SECKEY' => $this->secretKeyFlutterWave,

            //'narration' => "NaijaSub Wallet Funding through Card Payment. Powered by Flutterwave Payment Gateway API" //"Service Payment"
        );
        
        //"https://api.ravepay.co/flwv3-pug/getpaidx/api/tokenized/charge"
        //"https://api.flutterwave.com/v3/payments" - new endpoint...
        $request = $this->client->request('POST', "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay" , [
            'json' => $payload
        ]);

        $result = json_decode($request->getBody()->getContents(), TRUE);

        if ($result['status'] === 'success') {

            $response = $this->verifyPaymentFlutter($tnxid, $amount);

            if ($response['status'] == 'success') {

                General::logActivities($card_details->uid, 'Funding account with ' . $amount . ' using card: ' . $card_id);

                Transaction::where(['payment_reference' => $tnxid])->update([
                    'status' => 'success'
                ]);

                $this->creditWallet($card_details->uid, $amount, $reference);

                return array(
                    'status' => 'success',
                    'amount' => $response['amount'],
                    'message' => 'Account credited successfully'
                );

            } else {

                General::logActivities($card_details->uid, 'Funding verification of  ' . $amount . ' using card: ' . $card_id . ' failed');

                Transaction::where(['payment_reference' => $tnxid])->update([
                    'status' => 'failed'
                ]);

                return array(
                    'status' => 'failed', 
                    'message' => 'failed payment verification'
                );
            }

        } else {

            General::logActivities($card_details->uid, 'Funding payment of  ' . $amount . ' using card: ' . $card_id . ' failed');

            Transaction::where(['payment_reference' => $tnxid])->update([
                'status' => 'declined'
            ]);

            return array('status' => 'declined', 'message' => 'Funding has been declined by payment gateway provider');

        }
    }
    

    public function verifyBvn($bvn)
    {

        $request = $this->client->request('GET', "https://api.flutterwave.com/v3/kyc/bvns/" . $bvn, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKeyFlutterWave
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        if ($response['status'] == 'success') {

            return [
                'message' => /*json_encode(*/[
                        'bvn' => $response['data']['bvn'],
                        'first_name' => $response['data']['first_name'],
                        'middle_name' => $response['data']['middle_name'],
                        'last_name' => $response['data']['last_name'],
                        'gender' => $response['data']['gender'],
                        //'date_of_birth' => $response['data']['date_of_birth'],
                        'state_of_residence' => $response['data']['state_of_residence']
                    ]/*)*/
            ];
        }
        return ['message' => 'failed'];
    }

    public function providusLogin()
    {

        $client = new Client([
            'base_uri' => '127.0.0.1:8000',
        ]);


        $request = $client->request('POST', "https://api.monnify.com/api/v1/auth/login", [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->api_key . ':' . $this->secret_key)
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        return $response['responseBody']['accessToken'];

    }


    //note, for providus funding, we will be only checking the account balance of our providus wallet which must have been funded before now...  
    public function providusApiFunding($uid /*$amount, $providusFundingCheck*/)
    {
        //first authenticate to get the access token:
        $accessToken = $this->providusLogin();
        
        //assign a reference random number of length 12: 
        $reference = Str::random(12);
        
        //ensure the reference is unique:
        while (Transaction::where('reference', $reference)->count() > 0) {
            $reference = Str::random(12);
        }
        
        //create transaction before any reference:
        Transaction::create([
            'reference' => $reference,
            //'payment_reference' => $data['transactionReference'],
            'uid' => $uid,
            //'amount' => $amount,
            //'description' => "Successfully funded Providus Bank Wallet with {$amount} NGN " ,
            'transaction_type' => 'bank_funding',
            'status' => 'pending',
        ]);
        
        //check the Model to retrieve the User Providus:
        $userModel = User::where('id', $uid)->first();
        $providusAccountDetails = json_decode($userModel->providus_account); 
        
        //Wrong link to check account balance:
        /*"https://api.monnify.com/api/v2/transactions/" . $data['transactionReference']*/
        
        //initiate the request with guzzle library API:
        $request = $this->client->request('GET', "https://api.monnify.com/api/v2/disbursements/wallet-balance?accountNumber={$providusAccountDetails['account_number']}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken
            ]
        ]);
        
        $response = json_decode($request->getBody()->getContents(), true);

        if (($response['responseMessage'] === 'success') && ($response['responseCode'] == 0) ) {
            
            $availableProvidusBalance = $response['responseBody']['availableBalance']; 
            //$ledgerBalance = $response['responseBody']['ledgerBalance']
            
            //first check out the wallet:
            /*$walletModel = Wallet::where('wallet_id', uid)->first(); 
            $oldCurrentBalance = $walletModel->current_balance;*/
            
            if($availableBalance !== 0){
                /*$newCurrentBalance = $availableProvidusBalance + $oldCurrentBalance;*/
                //update wallet accordingly:
                /*$walletModel->update([
                    'previous_balance' => $oldCurrentBalance,
                    'current_balance' => $newCurrentBalance
                ]);*/
                
                $this->creditWallet($uid, $availableBalance, $reference);
                
                General::logActivities($uid, 'Funding of account with ' . $amount . ' through providus account was successful and your wallet has been credited acordingly!');

                Transaction::where(['uid' => $uid])->update([
                    'status' => 'success',
                    'amount' => $availableBalance,
                    'description' => "Successfully funded Wallet through Providus Bank Account with {$availableBalance} NGN " 
                ]);
            }
            
            return array(
                'status' => 'success',
                'amount' => $availableBalance,
                'message' => 'Account credited successfully'
            );            


        } else {
            General::logActivities($uid, 'Funding payment of  ' . $amount . ' using providus bank failed');

            Transaction::where(['uid' => $uid, 'reference' => $reference])->update([
                'status' => 'declined',
                'message'=> 'Account not credited successfully'
            ]);

            return array(
                'status' => 'declined', 
                'message' => 'Error in updating Funding through Providus Account. Declined through the payment gateway provider. Please retry again!'
            );
        }
    }


    public function providusAssignUserAccount($uid)
    {
        $user = User::where('id', $uid)->first();

        try {
            $accessToken = $this->providusLogin();

            $reference = Str::random(10) . '-' . $user['id'];

            $payload = [
                'accountReference' => $reference,
                'accountName' => $user['name'],
                'currencyCode' => 'NGN',
                'contractCode' => $this->contract_key,
                'customerEmail' => $user['email'],
                'customerName' => $user['name'],
                'restrictPaymentSource' => false,
            ];

            $request = $this->client->request('POST', "https://api.monnify.com/api/v1/bank-transfer/reserved-accounts", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'json' => $payload
            ]);

            $response = json_decode($request->getBody()->getContents(), TRUE);

            if ($response['requestSuccessful'] == true && $response['responseMessage'] == 'success' && $response['responseCode'] == 0) {

                $providusAccount = [
                    'account_name' => $response['responseBody']['accountName'],
                    'reference' => $response['responseBody']['accountReference'],
                    'account_number' => $response['responseBody']['accountNumber'],
                    'bank_name' => $response['responseBody']['bankName'],
                    'reservation_reference' => $response['responseBody']['reservationReference'],
                    'bank_code' => $response['responseBody']['bankCode']
                ];

                User::where('id', $uid)->update([
                    'providus_account' => json_encode($providusAccount)
                ]);


            }

        } catch (\Exception $e) {

            General::logActivities('system', 'Providus account creation for ' . $user['name'] . ' and user ID:' . $user['id']);

        }
    }

    public function disburseCashToUserAccount($amount, $reference, $narration, $bank_name, $account_number, $uid)
    {
        $accessToken = $this->providusLogin();

        $payload = [
            'amount' => $amount,
            'reference' => $reference,
            'narration' => $narration,
            'bankCode' => General::filterBankProvided($bank_name),
            'accountNumber' => $account_number,
            'currency' => 'NGN',
            'walletId' => $this->wallet_id_monify
        ];

        $request = $this->client->request('GET', "https://sandbox.monnify.com/api/v2/transactions/MNFY|20200626145121|001401", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken
            ]
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        if ($response['requestSuccessful'] == true && $response['responseMessage'] == 'success') {

            Transaction::where('reference', $reference)->update([
                'status' => 'success',
                'payment_reference' => $response['responseBody']['transactionReference']
            ]);

            General::logActivities($uid, "Withdraw amount of NGN" . $amount . ' has been credited to your account.');

            return array('message' => 'success');

        } else {

            Transaction::where('reference', $reference)->update([
                'status' => 'failed'
            ]);

            General::logActivities($uid, "Funding request of NGN" . $amount . ' failed');

            return array('message' => 'failed');
        }
    }
    
    
    public function transferFunding($amount, $receiver_wallet_id_or_username, $uid)
    {
        $reference = 'TR-' . Str::random(6);

        while (Transaction::where('reference', $reference)->count() > 0) {
            $reference = 'TR-' . Str::random(6);
        }
        
        //get the first name, middle name and last name of the sender:
        $senderFirstName = User::where(['id' => $uid])->first()['first_name'];
        $senderMiddleName = User::where(['id' => $uid])->first()['middle_name'];
        $senderLastName = User::where(['id' => $uid])->first()['last_name'];
        
        //get the first name, middle name and last name of the recipient:
        $recipientFirstName = User::where(['wallet_id' => $receiver_wallet_id_or_username])->first()['first_name'];
        
        if($recipientFirstName == ''){
            $recipientFirstName = User::where(['username' => $receiver_wallet_id_or_username])->first()['first_name'];
        }
        
        
        $recipientMiddleName = User::where(['wallet_id' => $receiver_wallet_id_or_username])->first()['middle_name'];
        
        if($recipientMiddleName == ''){
            $recipientMiddleName = User::where(['username' => $receiver_wallet_id_or_username])->first()['middle_name'];
        }
        
        
        $recipientLastName = User::where(['wallet_id' => $receiver_wallet_id_or_username])->first()['last_name'];
        
        if($recipientFirstName == ''){
            $recipientLastName = User::where(['username' => $receiver_wallet_id_or_username])->first()['last_name'];
        }
        
        //create a db transaction for inter-account transfer in database:
        Transaction::create([
            'reference' => $reference,//'TF' . time(),
            'uid' => $uid,
            'amount' => $amount,
            'description' => "transfer to {$recipientFirstName} {$recipientMiddleName} {$recipientLastName}",
            'transaction_type' => 'transfer_funding',
            'status' => 'pending'
        ]);
        
        //first check the wallet balance:
        $wallet_status = $this->checkWalletBalance($amount, $uid);

        if ($wallet_status['status'] == 'failed'){
            //return $wallet_status;
            return array(
                'status' => 'failed',
                'message' => 'Insufficient Balance in your account!'
            );
        }

        // debit sender
        self::debitWallet($uid, $amount, $reference);

        // credit receiver using wallet id or username as search criteria:
        $userModelThroughWalletId = User::where(['wallet_id' => $receiver_wallet_id_or_username])->first();
        
        $userModelThroughUsername = User::where(['username' => $receiver_wallet_id_or_username])->first();
        
        self::creditWallet( ($userModelThroughWalletId->id) ?: ($userModelThroughUsername->id), $amount);

        General::logActivities($uid, "Made a transfer of {$amount} to {$recipientFirstName} {$recipientMiddleName} {$recipientLastName}");

        General::logActivities(
            User::where(['wallet_id' => $receiver_wallet_id_or_username])->first()['id'], "Received a transfer of {$amount} from {$senderFirstName} {$senderMiddleName} {$senderLastName}");
        
        //update the transactional status:
        Transaction::where(['uid'=>$uid, 'reference'=> $reference])->update([
            'status' => 'success'
        ]);
            
        return array(
            'status' => 'success',
            'amount' => $amount,
            'message' => 'Account funded successfully!'
        );
    }

    /**
     * @param $amount
     * @param $uid
     * @return string[]
     */
    public function checkWalletBalance($amount, $uid)
    {
        $wallet_id = User::where(['id' => $uid])->first()['wallet_id'];

        $wallet_info = Wallet::where(['wallet_id' => $wallet_id])->first();
        
        //chack if there is sufficient wallet balance - 
        if ($wallet_info->current_balance >= $amount) {
            return array('status' => 'success', 'message' => '');
        }

        return array('status' => 'failed', 'message' => "Insufficient Balance");
    }

    public function debitWallet($uid, $amount, $transaction = '')
    {
        //deduct from this user's account:
        $wallet_id = User::where(['id' => $uid])->first()['wallet_id'];

        $wallet_info = Wallet::where(['wallet_id' => $wallet_id])->first();

        $update_amount_wallet = $wallet_info->current_balance - $amount;

        Wallet::where(['wallet_id' => $wallet_id])->update([
            'previous_balance' => $wallet_info->current_balance,
            'current_balance' => $update_amount_wallet
        ]);
        
        
        //fire a debit wallet event:
        $debitWalletEventPayload = [
            'amount' => $amount,
            'uid' => $uid,
            'transaction' => $transaction,
            'other' =>['transfer_option' => false],
            'description' => 'A debit transaction of  N' . number_format($amount, 2) . ' has just occurred on your NiajaSub Wallet. The details below:'
        ];

        event( new Notifications('debit', $debitWalletEventPayload) );

    }

    public function creditWallet($uid, $amount, $transaction = '')
    {
        $wallet_id = User::where(['id' => $uid])->first()['wallet_id'];

        $wallet_info = Wallet::where(['wallet_id' => $wallet_id])->first();

        $update_amount_wallet = $wallet_info->current_balance + $amount;

        Wallet::where(['wallet_id' => $wallet_id])->update([
            'previous_balance' => $wallet_info->current_balance,
            'current_balance' => $update_amount_wallet
        ]);
        
        //fire an event here on Providus payment update:
        $eventPayload = [
            'amount' => $amount,
            'uid' => $uid,
            'transaction' => $transaction,
            'other' => ['transfer_option' => false],
            'description' => 'A credit transaction of  N' . number_format($amount, 2) . ' has just occurred on your NaijaSub Wallet. The details below:'
        ];
        
        event( new Notifications('credit', $eventPayload) );
    }
    

    public function manualFunding($amount, $uid, /*$banks,*/ $other_details)
    {

        $reference = Str::random(12);

        while (Transaction::where('reference', $reference)->count() > 0) {
            $reference = Str::random(12);
        }

        General::logActivities($uid, 'Requesting funding of ' . $amount);

        $sender_details = [
            'sender_name' => $other_details['sender_name'],
            'sender_account_number' => $other_details['sender_account_number']
        ];

        Transaction::create([
            'reference' => $reference,
            'uid' => $uid,
            'amount' => $amount,
            'description' => 'Requesting funding of ' . $amount,
            'transaction_type' => 'manual_funding',
            'status' => 'pending',
            'other_banks' => json_encode(other_details['banks']),
            'sender_details' => json_encode($sender_details)
        ]);

        return array(
            'status' => 'pending',
            'amount' => $amount,
            'message' => 'Direct funding Request Received. Awaiting approval!'
        );

    }

    public function airtimeFunding($amount, $uid, $other)
    {

        $reference = Str::random(12);

        while (Transaction::where('reference', $reference)->count() > 0) {
            $reference = Str::random(12);
        }

        General::logActivities($uid, 'Requesting funding of ' . $amount);

        $sender_details = [
            'type' => $other['sender_name'],
            'sender_phone' => $other['sender_phone'],
            'airtime_pins' => $other['airtime_pins'],
            'airtime_mode' => $other['airtime_mode']
        ];

        Transaction::create([
            'reference' => $reference,
            'uid' => $uid,
            'amount' => $amount,
            'description' => 'Requesting funding of ' . $amount,
            'transaction_type' => 'airtime_funding',
            'status' => 'pending',
            'sender_details' => json_encode($sender_details)
        ]);


        return array(
            'status' => 'success',
            'amount' => $amount,
            'message' => 'Account funded successfully'
        );

    }

    public function saveCard($uid, $data)
    {
        // check if card already used
        if (Card::where(['uid' => $uid, 'card_token' => $data['cardToken']])->count() == 0) {

            Card::create([
                'uid' => $uid,
                'expiry_date' => $data['expiryDate'],
                'card_bin' => $data['cardBin'],
                'last_4_digit' => $data['last4digit'],
                'brand' => $data['brand'],
                'card_token' => $data['cardToken'],
                'account_name' => $data['accountname']
            ]);

        }
    }
    

    public function checkIpAddress()
    {
        return request()->ip();
    }
    
    
    //admin can accept or decline manual funding:
    public function manualFundingRequest($uid, $tid, $status, $amount)
    {

        switch ($status) {
            case 'approve':
                $this->approveFunding($uid, $amount, $tid);
                break;
            case 'decline':
                $this->declineFunding($uid, $amount, $tid);
                break;
            default:
                General::logActivities($uid, 'Funding account failed');
        }

    }
    
    public function fundWalletWithCrypto($amount, $uid) : array{
        //admin crypto wallet address is displayed on the frontend so that the user can pay into it.....
        
        //generate reference:
        $reference = Str::random(12);
        
        //ensure unique:
        while (Transaction::where('reference', $reference)->count() > 0) {
            $reference = Str::random(12);
        }

        General::logActivities($uid, "Requesting funding of {$amount} through crypto - btc");
        
        $senderFirstName = User::where(['id' => $uid])->first()['first_name'];
        $senderMiddleName = User::where(['id' => $uid])->first()['middle_name'];
        $senderLastName = User::where(['id' => $uid])->first()['last_name'];
        
        $sender_details = [
            'senderFirstName' => $senderFirstName ,
            'senderMiddleName'=>$senderMiddleName,
            'senderLastName'=>$senderLastName
        ];
        
        //create pending tranaction in db:
        Transaction::create([
            'reference' => $reference,
            'uid' => $uid,
            'amount' => $amount,
            'description' => "Requesting funding of {$amount} through crypto - btc",
            'transaction_type' => 'crypto_funding',
            'status' => 'pending',
            'sender_details' => json_encode($sender_details)
        ]);
        
        //Still pending, awaiting approval by the admin:
        
        return array(
            'status' => 'pending',
            'amount' => $amount,
            'message' => 'Wallet not funded yet'
        );
    }
    
    //withdraw user wallet balance back to bitcoin wallet:$data - $amount and reference...
    public function withdrawFromWalletToCoin($data){
        
        //get the uid:
        $uid = Transaction::where(['reference'=>$data['reference']])->first()['uid'];
        
        //debit wallet:
        $this->debitWallet($uid, $data['amount'], $data['reference']);
        
        //purchase the coin:
        CoinPayment::purchaseCoin($data);
        
        $message = "Witrhdrawal request of NGN {$data['amount']} has been approved!";

        General::logActivities($uid, $message);
        
        //check this out later...
        self::paymentLog($uid, 'db', 'crypto_withdrawal', $amount, 'success');
        
        return array(
            'status' => 'success',
            'amount' => $amount,
            'message' => 'Withdrawal from naijasub to btc wallet was successful'
        );
    }
    
    
    //admin can accept or decline manual funding:
    public function approveFunding($uid, $amount, $tid)
    {
        //direct funding has no service charge deduction:
        //$amount = $amount - $this->fundingCharges('manual');

        $reference = Transaction::where('id', $tid)->first()['reference'];

        // credit user
        $this->creditWallet($uid, $amount, $reference);

        Transaction::where('id', $tid)->update([
            'status' => 'approved'
        ]);

        $message = "Funding request of NGN {$amount} has been approved!" . /*$amount + $this->fundingCharges('manuel') . '  //and service charge of NGN' . //$this->fundingCharges('manuel');*/

        General::logActivities($uid, $message);
        
        //check this out later...
        self::paymentLog($uid, 'cr', 'manuel_funding', $amount, 'success');
    }
    
    /*Note: before approval, there must have been a UI option on the admin end where
    he can type in what he received and it will be automatically convert to naira value
    (using bitCoin conversion util in CoinPayment.php)*/
    
    //admin can accept or decline crypto funding too:
    public function approveCryptoFunding($amount, $uid){
        
        $reference = Transaction::where('uid', $uid)->first()['reference'];

        // credit user
        $this->creditWallet($uid, $amount, $reference);

        Transaction::where('id', $tid)->update([
            'status' => 'approved'
        ]);

        $message = "Funding request of NGN {$amount} has been approved!" . /*$amount + $this->fundingCharges('manuel') . '  //and service charge of NGN' . //$this->fundingCharges('manuel');*/

        General::logActivities($uid, $message);
        
        //check this out later...
        self::paymentLog($uid, 'cr', 'manuel_funding', $amount, 'success');
    }
    

    public function declineFunding($uid, $amount, $tid)
    {
        Transaction::where('id', $tid)->update([
            'status' => 'declined'
        ]);

        $message = 'Funding request of NGN' . $amount . ' has been declined';

        General::logActivities($uid, $message);
    }
    


    public function fundWalletWithWalletId($wallet_id, $amount, $sid)
    {
        $this->creditWallet(User::where('wallet_id', $wallet_id)->first()['id'], $amount);

        self::paymentLog(User::where('wallet_id', $wallet_id)->first()['id'], 'cr', 'direct_wallet_funding', $amount, 'success');

        General::logActivities(User::where('wallet_id', $wallet_id)->first()['id'], 'Account funded with NGN' . $amount);

    }

    public function debitWalletWithWalletId($wallet_id, $amount, $sid, $reason)
    {
        $this->debitWallet(User::where('wallet_id', $wallet_id)->first()['id'], $amount);

        self::paymentLog(User::where('wallet_id', $wallet_id)->first()['id'], 'dr', 'direct_wallet_funding', $amount, 'success');

        General::logActivities(User::where('wallet_id', $wallet_id)->first()['id'], 'Account funded with NGN' . $amount);
    }

    public static function paymentLog($uid, $type, $funding_type, $amount, $status, $reason = 'Personal funding', $sid = 'user')
    {
        $payment = new PaymentLog();

        $payment->uid = $uid;

        $payment->type = $type;

        $payment->funding_type = $funding_type;

        $payment->amount = $amount;

        $payment->sid = $sid;

        $payment->status = $status;

        $payment->reason = $reason;

        $payment->save();
    }
}
