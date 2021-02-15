<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Service\Funding;
use App\Http\Controllers\Service\General;
use App\Http\Controllers\Service\ServiceRender;

use App\Events\ReferralUpgrade;

use App\AdminBanks;
use App\AdminPhone;
use App\Card;
use App\Coin;
use App\Epin;
use App\FundingCharge;
use App\Log;
use App\MembershipPlan;
use App\MembershipUpgradeLog;
use App\Notification;
use App\Packages;
use App\Services;
use App\Setting;
use App\SubServices;
use App\Transaction;
use App\User;
use App\UserPin;
use App\Wallet;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public $service_provider = 'NS';

    public $url = 'https://api.naijasub.com/storage/';

    public function fetchDashboard(Request $request)
    {

        $status = array();
        
        //authenticated user:
        $user = $request->user();

        try {

            $type = ['airtime', 'data', 'cable', 'electricity'];
            
            //build response:        
            $status['data'] = [
                        
                'wallet' => Wallet::where('wallet_id', $user->wallet_id)->first(),
                        
                'last_tnx' => Transaction::with(['fetchSubService', 'fetchPackages'])->where('uid', $user->id)->orderBy('created_at', 'DESC')->first(),
                        
                'pin_status' => UserPin::where('uid', $user->id)->count() > 0,
                        
                'transaction' => Transaction::where('uid', $user->id)->orderBy('created_at', 'DESC')->take(8)->get(),
                        
                'services' => Services::where('status', 'active')->get(),
                        
                'nscoin' => Coin::where('uid', $user->id)->get(),
                        
                'total_tnx' => Transaction::where('status', 'success')->whereIn('transaction_type', $type)->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->count(),
                        
                'total_sales' => Transaction::where('status', 'success')->whereIn('transaction_type', $type)->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount'),
                        
                'total_api_tnx' => 0
                
            ];
            
            //return response:
            return response()->json($status, 200);

        } catch (\Exception $e) {
            
            //build response:
            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = "Error in fetching dashboard data! Pls refresh and try again!";//$e->getMessage();
            
            //return response:
            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }

    public function fundMethods(Request $request)
    {
        $status = array();
        try {
            
            $providus_bank = json_decode(User::where('id', $request->user()['id'])->first()['providus_account'], true);
            
            //build response:
            $status = [
                'data' => [
                        
                    'method' => [
                            
                        [
                            'name' => 'manual', 
                            'index' => 1, 
                            'display' => 'Direct Bank Payment',
                            'charges' => 
                                (FundingCharge::where('type', 'manuel')->first()['amount']) ? FundingCharge::where('type', 'cards')->first()['amount'] : 0
                        ],
                                
                        [
                            'name' => 'flutter', 
                            'index' => 2, 
                            'display' => 'Pay with Card',
                            'charges' => 
                                (FundingCharge::where('type', 'flutter')->first()['amount']) ? FundingCharge::where('type', 'cards')->first()['amount'] : 0
                        ],
                                    
                        [
                            'name' => 'cards', 
                            'index' => 3, 
                            'display' => 'Saved Cards',
                            'charges' => 
                                (FundingCharge::where('type', 'cards')->first()['amount']) ? FundingCharge::where('type', 'cards') > first()['amount'] : 0
                        ],
                                    
                        [
                            'name' => 'pay_airtime', 
                            'index' => 4, 
                            'display' => 'Pay with Airtime',
                            'phone_number' => AdminPhone::get(),
                            'charges' => 
                                (FundingCharge::where('type', 'pay_airtime')->first()['amount']) ? FundingCharge::where('type', 'cards')->first()['amount'] : 0
                        ],
                            
                        [
                            'name' => 'providus', 
                            'index' => 5, 
                            'display' => 'Pay with Providus (Recommended)',
                            'providus_bank' => [
                                'account_name' => $providus_bank['account_name'],
                                'bank_name' => $providus_bank['bank_name'],
                                'account_number' => $providus_bank['account_number']],
                            'charges' => 
                                (FundingCharge::where('type', 'providus')->first()['amount']) ? FundingCharge::where('type', 'cards')->first()['amount'] : 0
                        ],
                                
                        [   
                            'name' => 'transfer', 
                            'index' => 6, 
                            'display' => 'Inter-wallet Transfer',
                            'charges' => 
                                (FundingCharge::where('type', 'transfer')->first()['amount']) ? FundingCharge::where('type', 'cards')->first()['amount'] : 0
                        ]
                    ],
                        
                    'cards' => Card::where('uid', $request->user()['id'])->get(),
                        
                    'available_banks' => AdminBanks::get(),
                        
                    'wallet' => Wallet::where('wallet_id', $request->user()['wallet_id'])->first(),
                        
                    'transaction_pin' => UserPin::where('uid', $request->user()['id'])->count() > 0,
                        
                    'charge' => FundingCharge::get()
                    
                ]
            ];
            
            //return response:
            return response()->json( $status, 200);
            
        } catch (\Exception $e) {
            
            //build response:
            $status["code"] = 0;
            $status["message"] = "An error occured";
            $status["short_description"] = "Error in retreiving funding methods! Please try again later";//$e->getMessage()
            
            //return response:
            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function fetchFunding(Request $request)
    {
        $status = array();

        try {

            $transaction_type = ['gateway_funding', 'bank_funding', 'transfer_funding', 'manual_funding'];

            $status['data']['fund'] = Transaction::whereIn('transaction_type', $transaction_type)->orderBy('created_at', 'DESC')->paginate(30);

            $status['data']['wallet'] = Wallet::where('wallet_id', $request->user()['wallet_id'])->first();

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'Funding transactions found!';

        } catch (\Exception $e) {
            
            //build response:
            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = 'Error in fetching funding transactions';//$e->getMessage();
            
            //return response:
            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

        return response($status, 200);
    }

    //This uses the Paystack/Flutter(even Rave) BVN API to verify user...
    //(we use the Guzzle http library) 
    public function verifyWalletUser(Request $request)
    {
        $status = array();
        try {

            $wallet_id = $request->input('wallet_id');
            
            $userModel = User::where('wallet_id', $wallet_id);
            if ($userModel->count() == 0) {
                throw new \Exception('Invalid wallet id provided', 400);
            }

            $user_details = $userModel->first();
            
            //build response:
            $status["message"] = 'Wallet details';
            $status["data"] = [
                'name' => $user_details['name'],
                'phone' => $user_details['phone']
            ];

            return response()->json($status, 200);

        } catch (\Exception $e) {
            
            //build response:
            $status["message"] = 'An error occurred in validating user wallet';
            $status["short_description"] = '$e->getMessage()';//. $e->getLine()
             
            //return  response:
            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function fundWallet(Request $request)
    {
        $status = array();

        try {
            
            //get the user object from request:
            $userObject = $request->user();

            $funding_method = $request->input('funding_method'); //this will be sent as "providus" when the user clicks check for payment (in the providus section) in the UI***

            $amount = $request->input('amount');

            // when using flutter or paystack a txn_id is required
            $payment_tnx = $request->input('payment_tnx');

            // manual transfer parameters - this will be received by both the wallet ID or the username
            $user_to_receive = $request->input('wallet_id');
            //(Note: user can input either the receiver's wallet_id or username here...update on the frontend accordingly) 

            // manual funding
            $sender_name = $request->input('sender_name');
            $sender_account_number = $request->input('sender_account_number');

            $card_id = $request->input('card_id');

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed'){
                throw new \Exception('Transaction pin verification failed', 400);
            }

            $payment = new Funding();

            $payload = [
                'method' => $funding_method,//expecting providus here in providus  funding:
                'card_id' => $card_id,
                'amount' => $amount,
                'receiver_id' => $user_receive,
                'tnx' => $payment_tnx,
                'uid' => $request->user()['id'],
                'other' => [
                    'banks' => $request->input('bank_id'),
                    'providus' => $request->input('providus'),//this can be in form of button check (i.e check Account Wallet to confirm funding here:)  
                    'sender_name' => $sender_name,
                    'sender_account_number' => $sender_account_number,
                    'airtime_mode' => $request->input('airtime_mode'),
                    'phone_number' => $request->input('phone_number'),
                    'sender_phone' => $request->input('sender_phone'),
                    'airtime_pins' => $request->input('airtime_pins'),
                ]
            ];

            $result = $payment->fundingMethod($payload);
            
            //init status:
            $status = array();
            
            if ($result['status'] == 'failed'){
                
                throw new \Exception($result['message'], 400);
                
            }else if($result['status'] == 'pending'){
        
                $status['code'] = 1;

                $status['message'] = 'pending';

                $status['short_description'] = $result['message'];
                
            }else{
            
                //now convert the ns_coin to real money and input into database as appropriate: 
                General::convertNScoinToRealMoney($userObject);
            
                $status['code'] = 1;

                $status['message'] = 'success';

                $status['short_description'] = "Successfully funded account. Your 100 NS coin is now redeemed to 50NGN in your main account!";
                
            }
            
            return response()->json($status, 200);

        } catch(\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getFile() . $e->getLine();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }

    public function createTransactionPin(Request $request)
    {

        $status = array();

        try {

            $user = $request->user();

            $pin = $request->input('pin');

            if (count(str_split($pin)) < 4)
                throw new \Exception("Transaction pin can't be less than 4 digit", 400);
            
            $userModel = UserPin::where(['uid' => $user->id]);
            
            if($userModel->count() > 0){
                throw new \Exception('Pin already created.');
            }
            //create the pin:
            /*UserPin::create([
                'uid' => $user->id,
                'pin' => password_hash($pin, PASSWORD_DEFAULT),
                'last_updated' => Carbon::now()
            ]);*/
            
            //now update the model accordingly:
            $userModel->update([
                'pin' => password_hash($pin, PASSWORD_DEFAULT),
                'last_updated' => Carbon::now()
            ]);

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'pin created';
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }

    public function updateTransactionPin(Request $request)
    {

        $status = array();

        try {

            $user = $request->user();

            $pin = $request->input('pin');

            $old_pin = $request->input('old_pin');

            if (count(str_split($pin)) < 4)
                throw new \Exception("Transaction pin can't be less than 4 digit", 400);

            $user_pin_details = UserPin::where(['uid' => $user->id])->first();

            if (!$user_pin_details)
                throw new \Exception('Contact support to verify account or Re-try login', 400);

            if (!password_verify($old_pin, $user_pin_details->pin))
                throw new \Exception('Invalid old pin provided', 400);

            UserPin::where(['uid' => $user->id, 'pin' => $old_pin])->update([
                'pin' => password_hash($pin, PASSWORD_DEFAULT),
                'last_updated' => Carbon::now()
            ]);

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'pin updated';
            
             return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }

    public function upgradeMembership(Request $request)
    {

        $status = array();

        try {

            $user = $request->user();

            $upgrade_membership_level = $request->input('upgrade_membership_level');

            if (empty($upgrade_membership_level)){
                throw new \Exception('Membership upgrade level is required', 400);
            }
            // get user current level:
            $user_current_level = $user->membership_level;

            // fetch upgrade level details
            $upgrade_level_details = MembershipPlan::where(['id' => $upgrade_membership_level])->first();

            $funding = new Funding();

            $check_wallet_balance = $funding->checkWalletBalance($upgrade_level_details->upgrade_amount, $user->id);

            if ($check_wallet_balance['status'] == 'failed'){
                throw new \Exception($check_wallet_balance['message'], 400);
            }
            
            $funding->debitWallet($user->id, $upgrade_level_details->upgrade_amount);

            Log::create([
                'uid' => $user->id,
                'description' => 'Account was debited with the sum of ' . $upgrade_level_details->upgrade_amount . ' for upgrade to ' . $upgrade_level_details->name . ''
            ]);

            MembershipUpgradeLog::create([
                'uid' => $user->id,
                'current_plan' => $user->membership_level,
                'to_plan' => $upgrade_level_details->name
            ]);

            // upgrade account
            User::where(['id' => $user->id])->update([
                'membership_level' => $upgrade_level_details->name
            ]);

            General::logActivities($user->id, 'Account has been upgraded to ' . $upgrade_level_details->name);
            
            //fires an event here:
            event(new ReferralUpgrade($user->id, $upgrade_level_details->id));

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'upgrade account';
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }


    public function fetchServices(Request $request)
    {

        $status = array();

        try {

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'services';

            $status['data']['services'] = Services::where(['status' => 'active'])->get();

            $status['data']['wallet'] = Wallet::where('wallet_id', $request->user()['wallet_id'])->first();
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }


    public function fetchSubServices(Request $request)
    {

        $status = array();

        try {

            $service_id = $request->input('service_id');

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'sub services';

            $services = Services::where(['status' => 'active', 'display_name' => $service_id])->first();

            $status['data']['services'] = $services;

            $status['data']['sub_service'] = SubServices::with(['fetchPackages'])->where(['service_id' => $services['id']])->get();

            $status['data']['eligible_e_pin_generation'] = 'false';

            if ($service_id == 'e_pin') {
                // check if eligible
                $membership_bool = ['dealer', 'executive', 'platium'];

                if (in_array($request->user()['membership_level'], $membership_bool) || User::where('id', $request->user()['id'])->first()['e_pin'] == 'subscribed') {
                    $status['data']['eligible_e_pin_generation'] = 'true';
                }
            }

            $status['data']['dollar_rate_selling'] = Setting::where('type', 'dollar_rate_selling')->first()['keys'];

            $status['data']['dollar_rate_buying'] = Setting::where('type', 'dollar_rate_buying')->first()['keys'];

            $status['data']['wallet'] = Wallet::where('wallet_id', $request->user()['wallet_id'])->first();

            $status['data']['phone_number'] = AdminPhone::get();
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = "Error in fetching sub-services";//$e->getMessage();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }


    public function fetchPackages(Request $request)
    {

        $status = array();

        try {

            $sub_service_id = $request->input('sub_service_id');

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'packages';

            $status['data']['packages'] = Packages::where(['sid' => $sub_service_id])->paginate(20);
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = "Error in fetching packages!";//$e->getMessage() . $e->getLine();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }


    public function initiatePurchase(Request $request)
    {
        $status = array();

        try {
            
            $service_id = $request->input('service_id');
            $sub_service_id = $request->input('sub_service');
            $number = $request->input('service_number');
            $service_type = $request->input('service_type');
            $amount = $request->input('amount');

            // fetch medium
            $temp = SubServices::where('sid', $sub_service_id)->first()['medium'];

            $medium = explode(':', $temp);

            $data = [
                'number' => $number,
                'service_id' => $service_id,
                'sub_service_id' => $sub_service_id,
                'service_type' => Str::lower($service_type),
                'membership_level' => $request->user()['membership_level'],
                'amount' => $amount,
                'electricity_type' => $request->input('electricity_type'),
                'medium_split' => (Str::lower($service_type) == 'electricity') ? explode(',', $medium[1]) : ''
            ];

            $service = ServiceRender::initTransaction($medium[0], $data);
            
            //add the header method bcos of the browser cors warning..
            return response()->json(array(
                'data' => [
                    'response' => $service
                ]
            ), 200)->header("Access-Control-Allow-Origin",  "*");

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = "Error in initiating purchase. Please try again!";//$e->getMessage() . $e->getFile();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }


    public function purchaseAirtime(Request $request)
    {

        $status = array();

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed')
                throw new \Exception('Transaction pin verification failed', 400);


            $phone_number = $request->input('phone_number');

            $amount = $request->input('amount');

            $service = $request->input('service_id');
            
            $product_code = $request->input('product_id');

            $rule = array(
                'phone_number' => 'Required',
                'amount' => 'Required',
                'service_id' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $user = $request->user();
            
              // initiate funding class
            $funding = new Funding();

            $reference = 'AIR' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'AIR' . Str::upper(Str::random(8));
            }

             Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Airtime purchase of ' . $amount . ' on ' . $phone_number,
                'transaction_type' => 'airtime',
                'status' => 'pending',
                'sub_service_id' => $service,
            ]);
          
            $data = [
                'sid' => $service,
                'amount' => $amount,
                'reference' => $reference,
                'uid' => $user->id,
                'phone_number' => $phone_number,
                'email' => $user->email,
                'product_code' =>  $product_code
            ];

          
            $wallet_balance = $funding->checkWalletBalance($amount, $user->id);

            if ($wallet_balance['status'] == 'failed'){
                throw new \Exception($wallet_balance['message'], 400);
            }

            // check if provider is NS
            if ($this->service_provider == 'NS') {
                $members_packages = MembershipPlan::where(['name' => $user->membership_level])->first();

                $amount = $amount - (($members_packages->airtime_discount / 100) * $amount);

            }
            
            
            
            // debit user and process airtime credit
            $funding->debitWallet($user->id, $amount);
            General::logActivities($user->id, 'Wallet debit of ' . $amount . ' occurred on your wallet for airtime purchase to ' . $phone_number);

            ServiceRender::initiateTransaction('AIRTIME_PURCHASE', $data);

            $status['code'] = 1;
            $status['message'] = 'success';
            $status['short_description'] = 'purchase airtime';
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }
    

    public function sellAirtime(Request $request)
    {

        try {
            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed')
                throw new \Exception('Transaction pin verification failed', 400);

            $user = $request->user();

            $amount = $request->input('amount');

            $type = $request->input('type'); // share and sell or airtime

            $pin = $request->input('pin');

            $network = $request->input('network');

            if ($type == $pin) {
                if (empty($pin)){
                    throw new \Exception('Pin code is required ', 400);
                }
            }

            $phone_number = $request->input('phone');

            $reference = 'AIR_SELL' . Str::random(10);

            while (Transaction::where('reference', $reference)->count() > 0) {
                $reference = 'AIR_SELL' . Str::random(10);
            }

            $service_id = SubServices::where('display_name', Str::lower($network))->first()['sid'];

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => Str::upper($network) . ' airtime sale of N' . number_format($amount),
                'transaction_type' => 'airtime',
                'status' => 'pending',
                'sub_service_id' => $service_id,
                'sender_details' => json_encode(['from' => ['phone' => $phone_number, 'type' => 'sell'], 'pin' => $pin]),
                'email' => $user->email
            ]);

            return response()->json(
                array(
                    'message' => 'Transaction successful. Wallet would be credited in a few minutes.'
                ),
                200
            );

        } catch (\Exception $e) {
            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine() . $e->getFile();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function purchaseData(Request $request)
    {

        $status = array();

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed'){
                throw new \Exception('Transaction pin verification failed', 400);
            }
            
            //NOTE: When the user fills the data volume(e.g 3GB(Options will be provided to choose from on the frontend)), 
            //the amount field will auto-fill itself... VERY IMPORTANT
            $phone_number = $request->input('phone_number');
            $network = $request->input('network');
            $data_volume = $request->input('data_volume');
            //$amount = $request->input('amount');
            $package_id = $request->input('package_id');

            $rule = array(
                'phone_number' => 'Required',
                'network' => 'Required',
                'data_volume' => 'Required',
                //'amount' => 'Required'
                'package_id' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed()){
                throw new \Exception($error->first(), 400);
            }
            
            $user = $request->user();

            $reference = 'DAT' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'DAT' . Str::upper(Str::random(8));
            }

            $package_details = Packages::where('pid', $package_id)->first();

            $amount = $package_details->amount;
            
            //get Admin Phone Number:
            $adminPhone = AdminPhone::get()->first()['phone'];
            
            //payload to deliver to executing function:
            $data = [
                'reference' => $reference,
                'uid' => $user->id,
                'package_id' => $package_id,
                'email' => $user->email,
                'adminPhoneNumber' => $adminPhoneNumber,
                'phone_number' => $phone_number,
                'network' => $network,
                'data_volume' => $data_volume,
                'amount' => $amount,
            ];
            
            $description = "Data purchase worth {$amount} on {$phone_number} purchased at {$discountedAmount} based on bonus available to user with current membership plan";
        
            //create a pending transaction in database:
            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => $description,
                'transaction_type' => 'data',
                'status' => 'pending',
                'sub_service_id' => $package_details->sid,
                'packages_id' => $package_id
            ]);

            //call out to external API:
            $isPurchasedDataSuccessful = ServiceRender::initiateTransaction('DATA_PURCHASE', $data);
            
            if( ($isPurchasedDataSuccessful === FALSE) ){
                
                throw new \Exception('Error in data purchase! Please retry again', 400);
                
            }else{
                
                //data successfully purchased, either through bulk sms or smeify API;
                
                // now check wallet balance:
                $funding = new Funding();
                $wallet_balance = $funding->checkWalletBalance($amount, $user->id);

                if ($wallet_balance['status'] == 'failed'){
                    throw new \Exception($wallet_balance['message'], 400);
                }

                // check if provider is NS:
                if ($this->service_provider == 'NS') {
                    $members_packages = MembershipPlan::where(['name' => $user->membership_level])->first();
                
                    //get the discounted amount:
                    $discountedAmount = $amount - (($members_packages->data_discount / 100) * $amount);
                }
            
                // debit user with the discounted amount based on the package he is:
                $funding->debitWallet($user->id, $discountedAmount);
            
                General::logActivities($user->id, 'Wallet debit of ' . $amount . ' occurred on your wallet for data purchase to ' . $phone_number);
            
                //change the status of this transaction://remember to check the event listeners that handled the above code...
                Transaction::where([
                    'uid' => $user->id, 
                    'reference' =>$reference
                ])->update([
                    'status' => 'success'
                ]);
                
                $notifyInfo = [
                    'uid'=> $user->id,
                    'amount' =>$amount,
                    'description'=> $description,
                    'transaction'=> 'Succeccfully purchased airtime'
                ];               
                
                event(new Notifications('debit', $notifyInfo));
                
                //update response status accordingly:
                $status['code'] = 1;
                $status['message'] = 'success';
                $status['short_description'] = 'purchase data';
            
                return response()->json($status, 200);
            }

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine().$e->getFile();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }
    
    
    public function purchaseInternet(Request $request)
    {

        $status = array();

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed'){
                throw new \Exception('Transaction pin verification failed', 400);
            }


            $phone_number = $request->input('phone_number');

            $package_id = $request->input('package_id');

            $rule = array(
                'phone_number' => 'Required',
                'package_id' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed()){
                throw new \Exception($error->first(), 400);
            }

            $user = $request->user();

            $reference = 'DA' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'DA' . Str::upper(Str::random(8));
            }

            $package_details = Packages::where('pid', $package_id)->first();

            $amount = $package_details->amount;

            $data = [
                'amount' => $amount,
                'phone_number' => $phone_number,
                'package_id' => $package_id,
                'uid' => $user->id,
                'email' => $user->email,
                'reference' => $reference
            ];

            // check wallet balance
            $funding = new Funding();
            $wallet_balance = $funding->checkWalletBalance($amount, $user->id);

            if ($wallet_balance['status'] == 'failed'){
                throw new \Exception($wallet_balance['message'], 400);
            }

            // check if provider is NS
            if ($this->service_provider == 'NS') {
                $members_packages = MembershipPlan::where(['name' => $user->membership_level])->first();

                $amount = $amount - (($members_packages->data_discount / 100) * $amount);

            }
            // debit user and process airtime credit
            $funding->debitWallet($user->id, $amount);
            General::logActivities($user->id, 'Wallet debit of ' . $amount . ' occurred on your wallet for data purchase to ' . $phone_number);

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Data purchase of ' . $amount . ' on ' . $phone_number,
                'transaction_type' => 'data',
                'status' => 'pending',
                'sub_service_id' => $package_details->sid,
                'packages_id' => $package_id
            ]);
            
            //the reason why I am allowing this to stay here is because there is a refund policy in the event architecture, 
            //otherwise, I would have moved this higher up. 
            ServiceRender::initiateTransaction('INTERNET_PURCHASE', $data);

            $status['code'] = 1;
            $status['message'] = 'success';
            $status['short_description'] = 'purchase data';
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine().$e->getFile();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }


    public function purchaseCableService(Request $request)
    {
        $status = array();

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed')
                throw new \Exception('Transaction pin verification failed', 400);


            $smart_card_number = $request->input('smart_card_number');

            $package_id = $request->input('package_id');

            $user = $request->user();

            $rule = array(
                'smart_card_number' => 'Required',
                'package_id' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $reference = 'AIR' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'AIR' . Str::upper(Str::random(8));
            }

            $package_details = Packages::where('pid', $package_id)->first();

            $amount = $package_details->amount;

            $data = [
                'amount' => $amount,
                'smart_card_number' => $smart_card_number,
                'package_id' => $package_id,
                'uid' => $user->id,
                'email' => $user->email,
                'reference' => $reference
            ];

            // check wallet balance
            $funding = new Funding();
            $wallet_balance = $funding->checkWalletBalance($amount, $user->id);

            if ($wallet_balance['status'] == 'failed')
                throw new \Exception($wallet_balance['message'], 400);

            // check if provider is NS
            if ($this->service_provider == 'NS') {
                $members_packages = MembershipPlan::where(['name' => $user->membership_level])->first();

                $amount = $amount - (($members_packages->data_discount / 100) * $amount);

            }
            // debit user and process airtime credit
            $funding->debitWallet($user->id, $amount);
            General::logActivities($user->id, 'Wallet debit of ' . $amount . ' occurred on your wallet for cable service purchase to ' . $smart_card_number);

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Cable purchase of ' . $amount . ' on ' . $smart_card_number,
                'transaction_type' => 'cable',
                'status' => 'pending',
                'sub_service_id' => $package_details->sid,
                'packages_id' => $package_id
            ]);

            ServiceRender::initiateTransaction('CABLE_PURCHASE', $data);

            $status['code'] = 1;
            $status['message'] = 'success';
            $status['short_description'] = 'purchase cable service';
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }
    

    public function purchaseElectricityService(Request $request)
    {
        $status = array();

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed')
                throw new \Exception('Transaction pin verification failed', 400);

            $meter_number = $request->input('meter_number');

            $service_id = $request->input('service_id');

            $amount = $request->input('amount');

            $user = $request->user();

            $rule = array(
                'meter_number' => 'Required',
                'service_id' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $sub_service_details = SubServices::where(['sid' => $service_id])->first()['min_amount'];

            if ($amount < $sub_service_details)
                throw new \Exception('Amount is less than Minimum purchase amount', 400);

            $reference = 'ELE' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'ELE' . Str::upper(Str::random(8));
            }

            $data = [
                'amount' => $amount,
                'meter_number' => $meter_number,
                'service_id' => $service_id,
                'uid' => $user->id,
                'reference' => $reference,
                'email' => $user->email,
                'phone_number' => (empty($request->input('phone_number'))) ? $request->user()['phone'] : $request->input('phone_number')
            ];

            // check wallet balance
            $funding = new Funding();
            $wallet_balance = $funding->checkWalletBalance($amount, $user->id);

            if ($wallet_balance['status'] == 'failed')
                throw new \Exception($wallet_balance['message'], 400);

            // check if provider is NS
            if ($this->service_provider == 'NS') {

                $members_packages = MembershipPlan::where(['name' => $user->membership_level])->first();

                $amount = $amount - (($members_packages->data_discount / 100) * $amount);

            }
            // debit user and process airtime credit
            $funding->debitWallet($user->id, $amount);
            General::logActivities($user->id, 'Wallet debit of ' . $amount . ' occurred on your wallet for cable service purchase to ' . $meter_number);

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Electricity purchase of ' . $amount . ' on ' . $meter_number,
                'transaction_type' => 'electricity',
                'status' => 'pending',
                'sub_service_id' => $service_id
            ]);

            ServiceRender::initiateTransaction('ELECTRICITY_PURCHASE', $data);

            $transaction = Transaction::where(['reference' => $reference])->first();

            while (empty($transaction->token)) {

                $transaction = Transaction::where(['reference' => $reference])->first();

                sleep(1);
            }

            $status['code'] = 1;
            $status['message'] = 'success';
            $status['short_description'] = 'purchase electricity service';
            $status['data']['token'] = $transaction;
            
            return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }
    

    public function sellCoin(Request $request)
    {
        $status = array();

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed'){
                throw new \Exception('Transaction pin verification failed', 400);
            }
            
            $userAmountToBuy = $request->input('amount');
            
            $admin_dollar_buying_rate = Setting::where('type', 'dollar_rate_buying')->first()['keys'];

            $amount =  $userAmountToBuy * $admin_dollar_buying_rate;

            $user = $request->user();

            $rule = array(
                'amount' => 'Required',
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed()){
                throw new \Exception($error->first(), 400);
            }

            $reference = 'CN' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'CN' . Str::upper(Str::random(8));
            }

            $data = [
                'amount' => $amount,
                'uid' => $user->id,
                'reference' => $reference,
                'channel' => 'SELL_COIN',
                'email' => $user['email']
            ];

            General::logActivities($user->id, 'Initiated coin sell');

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Sell coin worth NGN' . $amount,
                'transaction_type' => 'coin',
                'status' => 'pending'
            ]);

            ServiceRender::initiateTransaction('COIN_SELL', $data);

            $transaction = Transaction::where(['reference' => $reference])->first();

            while (empty($transaction->coin_details)) {

                $transaction = Transaction::where(['reference' => $reference])->first();

                sleep(1);
            }

            $status['code'] = 1;
            $status['message'] = 'success';
            $status['short_description'] = 'sell coin service';
            $status['data']['token'] = $transaction;
            
            return response()->json($status, 200);
            
        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //. $e->getLine() . $e->getFile();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }
    

    public function buyCoin(Request $request)
    {

        try {

            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed')
                throw new \Exception('Transaction pin verification failed', 400);

            $amount = $request->input('amount') * Setting::where('type', 'dollar_rate_buying')->first()['keys'];

            $user = $request->user();

            $rule = array(
                'amount' => 'Required',
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $reference = 'CN-Buy' . Str::upper(Str::random(8));

            while (Transaction::where(['reference' => $reference])->count() > 0) {
                $reference = 'CN-Buy' . Str::upper(Str::random(8));
            }

            $wallet_address = $request->input('wallet_address');


            if (!$user->wallet_address_coin) {

                if (empty($request->input('wallet_address'))) {
                    throw new \Exception('BTC Wallet address is required');
                }
                User::where('id', $user['id'])->update(['wallet_address_coin' => $request->input('wallet_address')]);
            }

            $data = [
                'amount' => $amount,
                'uid' => $user->id,
                'reference' => $reference,
                'channel' => 'COIN_PURCHASE',
                'email' => $user['email'],
                'wallet_address' => $wallet_address
            ];


            General::logActivities($user->id, 'Initiated buy coin service');

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Buy coin worth NGN' . $amount,
                'transaction_type' => 'coin',
                'status' => 'pending',
                'coin_details' => json_encode(array('type' => 'buy', 'wallet_details' => $wallet_address))
            ]);

            ServiceRender::initiateTransaction('COIN_PURCHASE', $data);

            return response()->json(
                array(
                    'message' => 'BTC purchase is successful and BTC wallet address would be credited soon. ',
                ),
                200
            );

        } catch (\Exception $e) {

            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage() //. $e->getLine() . $e->getFile()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }
    

    public function confirmCoin(Request $request)
    {

        $status = array();

        try {
            
            //check this out later, no try code: 
            return response()->json($status, 200);
        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage() . $e->getLine();

            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }
    

    public function profile(Request $request)
    {

        try {

            $user = $request->user();

            $transaction_type = ['gateway_funding', 'bank_funding', 'transfer_funding', 'manuel_funding'];

            $funding = Transaction::whereIn('transaction_type', $transaction_type)->where('uid', $user->id)->orderBy('created_at', 'DESC')->paginate(10);

            return response()->json(
                array(
                    'message' => 'profile',
                    'data' => [
                        'user_details' => $user,
                        'card' => Card::where('uid', $user->id)->get(),
                        'logs' => Log::where('uid', $user->id)->get(),
                        'wallets' => Wallet::where('wallet_id', $user->wallet_id)->first(),
                        'banks' => General::listBanksStatic(),
                        'level' => MembershipPlan::get(),
                        'user_level_update' => MembershipUpgradeLog::where('uid', $user->id)->get(),
                        'recent_funding' => $funding,
                        'transaction_pin' => (UserPin::where('uid', $user['id'])->count()) ? true : false
                    ]
                ), 200
            );

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => "Error in accessing your profile!"//$e->getMessage()
                ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }

    }


    //re-write this later:
    public function verifyAccountUsingBvn(Request $request)
    {
        $status = array(); 
        try {

            $user = $request->user();
            
            $email_verify = $request->input('email');
            
            //validate this email first:
            $rule = [email => 'Required|unique:users'];
            $validation = Validator::make($request->only('email'), $rule);
            
            if ($validation->failed()){
                throw new  \Exception('Invalid email provided! Please supply a valid email address', 400);
            }

            $bvn = $request->input('bvn');

            if (empty($bvn)) {
                throw new \Exception('BVN is required');
            }
            
              $file_name = time() . '.' . mt_rand(10, 200000) . $request->file('passport')->getClientOriginalName();

            $file_path = 'passport/' . $file_name;

            Storage::disk('local')->put($file_path, file_get_contents($request->file('passport')));

            $funding = new Funding();

            $verify = $funding->verifyBvn($bvn);

            if ($verify['message'] !== 'failed') {
                
                $userModel = User::where('id', $user['id']);
                
                if($userModel->email !== $email_verify){
                    $userModel->email = $email_verify;
                }
                
                //compare the received bvn first name and last name with the user provided details in the database:
                
                //bvn first and last name:
                $bvn_first_name = $verify['message']['first_name'];
                $bvn_last_name = $verify['message']['last_name'];
                
                //user supplied first and last name:
                $db_first_name = $userModel->first_name;
                $db_last_name = $userModel->last_name;
                
                if( ($bvn_first_name !== $db_first_name) && ($bvn_last_name !== $db_last_name) ){
                    
                    if( ($bvn_first_name !== $db_last_name) && ($bvn_last_name !==$db_first_name) ){
                    
                        throw new  \Exception('Cannot validate your details with BVN!', 400);
                    
                    } 
                    
                }else{
                    
                    //update the state of residence:
                    $bvn_state_of_residence = $verify['message']['state_of_residence'];
                    
                    if( $bvn_state_of_residence !== null ){
                        
                        $db_state_of_residence = $userModel->state_of_residence;
                        if($db_state_of_residence === null ){
                            //set new value:
                            $userModel->state_of_residence = $bvn_state_of_residence;
                        }
                    }
                    
                    //update model with other recieved details:
                    
                    $userModel->update([
                        
                        'middle_name' => $verify['message']['middle_name'],
                        
                        'gender' => $verify['message']['gender'],
                        
                        'bvn_verify' => 1,
                        
                        'passport' => $file_path,
                        
                        //store this in case you need it later:
                        'bvn_data' => json_encode($verify['message']),
                    ]);
                }
            }
            
            //build the response:
            $status['message'] = 'Verification Successful';
            $status['short_description'] = 'Account verified with BVN successfully!';
            
            //send response:
            return response()->json($status, 200);
            /*return response()->json(
                array(
                    'message' => 'An error occurred',
                    'short_description' => 'Invalid bvn account provided'
                ), 400
            );*/

        } catch (\Exception $e) {
            
            $status['message'] = 'An error occurred';
            $status['short_description'] = $e->getMessage();
            
            return response()->json($status, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }
    

    public function updateProfile(Request $request)
    {

        try {

            $user = $request->user();

            $name = $request->input('name');

            $email = $request->input('email');

            $phone = $request->input('phone');

            $rules = array(
                'name' => 'Required',
                'email' => 'Required',
                'phone' => 'Required'
            );

            $validation = Validator::make($request->all(), $rules);

            $errors = $validation->errors();

            if ($validation->failed())
                throw new  \Exception($errors->first(), 400);

            User::where('id', $user['id'])->update([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone')
            ]);

            if ($request->file('file')) {

                $file_name = time() . '.' . mt_rand(10, 200000) . $request->file('file')->getClientOriginalName();

                $file_path = 'profile/' . $file_name;

                Storage::disk('local')->put($file_path, file_get_contents($request->file('file')));

                User::where('id', $user['id'])->update([
                    'avater' => $file_path
                ]);

            }

            return response()->json(array(
                'message' => 'Successfully update profile',
                'data' => [
                    'user' => User::where('id', $user['id'])->first()
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }
    

    public function updatePassword(Request $request)
    {

        try {

            $user = $request->user();

            $password = $request->input('password');

            $confirm_password = $request->input('confirm_password');

            $old_password = $request->input('old_password');


            $rules = array(
                'old_password' => 'Required',
                'confirm_password' => 'Required',
                'password' => 'Required',
            );

            $validation = Validator::make($request->all(), $rules);

            $errors = $validation->errors();

            if ($validation->failed())
                throw new  \Exception($errors->first(), 400);

            if ($confirm_password != $password) {
                throw new \Exception('Password does not match', 400);
            }

            if (!password_verify($old_password, User::where('id', $user['id'])->first()['password'])) {
                throw new \Exception('Old password is in-correct', 400);
            }

            User::where('id', $user['id'])->update([
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            return response()->json(array(
                'message' => 'Password updated successfully',
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function updateBankInformation(Request $request)
    {

        try {

            $user = $request->user();

            $bank_name = $request->input('bank_name');

            $account_name = $request->input('account_name');

            $account_number = $request->input('account_number');
            
            $card_name =  $request->input('card_name');
            
            $card_number = $request->input('card_number');
            
            $card_expiry_month = $request->input('card_expiry_month');
            
            $card_expiry_year =  $request->input('card_expiry_year');
            
            $card_cvv =  $request->input('card_cvv');
            
            $card_token = $request->input('card_token');
    
            $rules;
            
            if($card_name == ""){
                
                $rules = array(
                    'bank_name' => 'Required',
                    'account_name' => 'Required',
                    'account_number' => 'Required',
                );
                
            }else if($card_name != ""){
                
                 $rules = array(
                    'bank_name' => 'Required',
                    'account_name' => 'Required',
                    'account_number' => 'Required|unique:users',
                    'card_name' => 'Required',  
                    'card_number' => 'Required|unique:users',
                    'card_expiry_month' => 'Required',
                    'card_expiry_year' => 'Required',
                    'card_cvv' => 'Required|unique:users',
                );
                
            }

            $validation = Validator::make($request->all(), $rules);

            $errors = $validation->errors();

            if ($validation->failed())
                throw new  \Exception($errors->first(), 400);


            User::where('id', $user['id'])->update([
                'account_number' => $account_number,
                'account_name' => $account_name,
                'account_bank' => $bank_name
            ]);
            
            //update card info:
            if ($card_name !== null){
                Card::where('id', $user->id)->update([
                    'account_name' => $account_name,
                    'card_brand' => $card_name,
                    'card_number' => $card_number,
                    'card_expiry_month' => $card_expiry_month,
                    'card_expiry_year' => $card_expiry_year,
                    'card_cvv'=> $card_cvv,
                    'card_token' => $card_token
                ]);
            }

            return response()->json(array(
                'message' => 'Update account information',
                'data' => [
                    'user' => User::where('id', $user['id'])->first(),
                    'card' => Card::where('id', $user['id'])->first()
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function withdraw(Request $request)
    {
        try {

            $user = $request->user();

            return response()->json(array(
                'message' => 'Update account information',
                'data' => [
                    'wallet' => Wallet::where('wallet_id', Auth::user()['wallet_id'])->first(),
                    'recent_transaction' => Transaction::where('uid', $user['id'])->orderBy('created_at', 'DESC')->take(10)->get(),
                    'withdraw_charges' => FundingCharge::where('type', 'withdraw')->first()['amount'],
                    'account_information' => (User::where('id', $user['id'])->first()['bvn_data'] == '') ? 'no' : 'yes'
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function makeWithdrawal(Request $request)
    {
        try {

            $user = $request->user();

            $amount = $request->input('amount');
            $pin = $request->input('transaction_pin');

            $verify_pin = General::verifyTransactionPin($pin, $request->user()['id']);

            if ($verify_pin['status'] == 'failed')
                throw new \Exception('Transaction pin verification failed', 400);

            // check wallet balance
            $funding = new Funding();
            $wallet_balance = $funding->checkWalletBalance($amount, $user->id);

            if ($wallet_balance['status'] == 'failed')
                throw new \Exception($wallet_balance['message'], 400);

            $reference = 'withdraw_NS' . Str::random(10);

            while (Transaction::where('reference', $reference)->count() > 0) {
                $reference = 'withdraw_NS' . Str::random(10);
            }

            Transaction::create([
                'reference' => $reference,
                'uid' => $user->id,
                'amount' => $amount,
                'description' => 'Withdraw request of NGN' . $amount,
                'transaction_type' => 'withdrawal',
                'status' => 'pending'
            ]);

            // debit user and process airtime credit
            $funding->debitWallet($user->id, $amount, $reference);

            General::logActivities($user->id, 'Wallet debit of ' . $amount . ' for withdrawal request is been processed');

            $amount_to_withdraw_charges = FundingCharge::where('type', 'withdraw')->first()['amount'];

            $amount_to_disburse = $amount - $amount_to_withdraw_charges;

            $approve__disbursal = $funding->disburseCashToUserAccount(
                $amount_to_disburse, $reference,
                'NaijaSub: withdrawal funding',
                $user['account_name'],
                $user['account_number'],
                $user['account_bank']);

            if ($approve__disbursal['message'] == 'success') {
                return response()->json(array(
                    'message' => 'Withdraw request as been successfully granted and cash debited to your account'
                ), 200);
            } else {
                throw new \Exception('Withdrawal request failed');
            }

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage() //. $e->getLine()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function transactions(Request $request)
    {
        try {

            $paginate = $request->input('paginate');

            if (empty($paginate)) {
                $paginate = 30;
            }

            return response()->json(array(
                'message' => 'All transactions',
                'data' => [
                    'transactions' => Transaction::where('uid', $request->user()['id'])->orderBy('created_at', 'DESC')->paginate($paginate)
                ]
            ), 200);


        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => "Unable to fetch all transactions successfully!"//$e->getMessage() . $e->getLine()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function transactionDetails(Request $request)
    {
        try {

            $transaction = Transaction::with(['fetchSubService', 'fetchSubService.fetchService', 'fetchPackages'])->where('reference', $request->input('reference'))->first();

            $details = [];

            switch ($transaction['transaction_type']) {
                case'manual_funding':
                    $details = [
                        'transaction_name' => 'Direct Bank Funding',
                        'type' => 'funding',
                        'charges' => 0,
                        'paid_into' => true,
                        'paid_into_details' => json_decode($transaction['other_banks'])
                    ];
                    break;
                case 'gateway_funding':
                    $details = [
                        'transaction_name' => 'Funding with card',
                        'type' => 'funding',
                        'charges' => '1.4%'
                    ];
                    break;
                case 'bank_funding':
                    $details = [
                        'transaction_name' => 'Payment with providus',
                        'type' => 'funding',
                        'charges' => 0
                    ];
                    break;
                case 'transfer_funding':
                    $details = [
                        'transaction_name' => 'Wallet Transfer',
                        'type' => 'funding',
                        'charges' => 0
                    ];
                    break;
                case 'airtime':
                    $details = [
                        'transaction_name' => 'Airtime Service',
                        'type' => 'airtime',
                        'charges' => 0,
                        'bonus' => General::fetchLevel($request->user()['membership_level'])['airtime_discount'],
                        'ns_coin_bonus' => General::fetchLevel($request->user()['membership_level'])['ns_coin_discount_amount'],
                        'is_sell' => json_decode($transaction['sender_details'], true)
                    ];
                    break;
                case 'data':
                    $details = [
                        'transaction_name' => 'Data Service',
                        'type' => 'data',
                        'charges' => 0,
                        'bonus' => General::fetchLevel($request->user()['membership_level'])['data_discount'],
                        'ns_coin_bonus' => General::fetchLevel($request->user()['membership_level'])['ns_coin_discount_amount'],
                    ];
                    break;
                case 'cable':
                    $details = [
                        'transaction_name' => 'Cable Service',
                        'type' => 'cable',
                        'charges' => 0,
                        'bonus' => General::fetchLevel($request->user()['membership_level'])['cable_discount'],
                        'ns_coin_bonus' => General::fetchLevel($request->user()['membership_level'])['ns_coin_discount_amount'],
                    ];
                    break;
                case 'electricity':
                    $details = [
                        'transaction_name' => 'Electricity Service',
                        'type' => 'electricity',
                        'charges' => 0,
                        'bonus' => General::fetchLevel($request->user()['membership_level'])['electricity_discount'],
                        'ns_coin_bonus' => General::fetchLevel($request->user()['membership_level'])['ns_coin_discount_amount'],
                    ];
                    break;
                case 'coin':
                    $details = [

                    ];
                    break;
                case 'withdrawal':
                    $details = [
                        'transaction_name' => 'Withdrawal Service',
                        'type' => 'withdraw',
                        'charges' => 25,
                        'bonus' => 0,
                        'ns_coin_bonus' => 0,
                    ];
                    break;
                case 'e_pin':
                    $details = [
                        'transaction_name' => 'E-Pin Services',
                        'type' => 'e_pin',
                        'charges' => 0,
                        'bonus' => 0,
                        'ns_coin_bonus' => 0,
                        'pin_generated' => Epin::where('purchased_by', $transaction['reference'])->get()
                    ];
                    break;
            }

            return response()->json(array(
                'message' => 'transactions details',
                'data' => [
                    'details' => $details,
                    'transactions' => $transaction
                ]
            ), 200);


        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => 'Unable to fetch all transactional details successfully!'//$e->getMessage() . $e->getLine()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function subscribeEpin(Request $request)
    {
        try {

            $funding = new Funding();

            $wallet_balance = $funding->checkWalletBalance(500, $request->user()['id']);

            if ($wallet_balance['status'] == 'failed') {
                throw new \Exception('Insufficient Balance', 400);
            }

            $funding->debitWallet($request->user()['id'], 500);

            User::where('id', $request->user()['id'])->update([
                'e_pin' => 'subscribed',
                'e_pin_details' => json_encode(['subscribed_time' => Carbon::now()])
            ]);

            General::logActivities($request->user()['id'], 'N500 was deducted from wallet for E-Pin subscription.');

            return response()->json(
                array(
                    'message' => 'Successfully subscribed account.'
                ),
                200
            );

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage() . $e->getLine()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function purchaseEPin(Request $request)
    {

        try {
            
            //this should be checked as soon as possible:
            $amount = $request->input('amount');//cost per e-pin

            $network = $request->input('network');

            $number_purchase = $request->input('purchase_count');//number of epin to be purchased

            $type = $request->input('type');

            $total_amount = $number_purchase * $amount;

            $funding = new Funding();

            // check wallet balance
            $wallet_balance = $funding->checkWalletBalance($total_amount, $request->user()['id']);

            if ($wallet_balance['status'] == 'failed') {
                throw new \Exception($wallet_balance['message'], 400);
            }

            $reference = 'EPIN_' . Str::random(10);

            while (Transaction::where('reference', $reference)->count() > 1) {
                $reference = 'EPIN_' . Str::random(10);
            }

            Transaction::create([
                'reference' => $reference,
                'uid' => $request->user()['id'],
                'amount' => $total_amount,
                'description' => 'E pin purchase of ' . Str::upper($network) . ' for N' . $total_amount,
                'transaction_type' => 'e_pin',
                'status' => 'pending',
            ]);

            $total_created = [];

            for ($i = 0; $i < $number_purchase; $i++) {
                
                //first check un-used e-pin:
                $epinCount = Epin::where([
                    'status' => 'un-used', 
                    'network_provider' => Str::lower($network), 
                    'amount' => $amount, 
                    'type' => Str::lower($type)
                ])->count();

                if ( $epinCount > 0 ) {

                    $first_e_pin = Epin::where([
                        'status' => 'un-used', 
                        'network_provider' => Str::lower($network), 
                        'amount' => $amount, 
                        'type' => Str::lower($type)
                    ])->first();

                    Epin::where([
                        'epin' => $first_e_pin['epin'], 
                        'status' => 'un-used',
                        'network_provider' => Str::lower($network), 
                        'amount' => $amount
                    ])->update([
                        'purchased_by' => $reference,
                        'status' => 'used',
                        'purchase_details' => json_encode([
                            'uid' => $request->user()['id'], 
                            'date_purchased' => Carbon::now()
                        ])
                    ]);

                    array_push($total_created, $i);
                    
                    $funding->debitWallet($request->user()['id'], $amount);
                }
                
                //make available as pdf:
                Storage::disk('local')->put('epin.pdf', $first_e_pin['epin']);
            }

            Transaction::where(['reference' => $reference])->update([
                'status' => 'success',
                'description' => count($total_created) . ' ' . $type . ' pin was generated at N' . $amount . ' each',
                'amount' => count($total_created) * $amount
            ]);

            General::logActivities($request->user()['id'], "E-Pin generated for transaction reference" . $reference);

            if (count($total_created) < $number_purchase) {

                $message = (count($total_created) == 0) ? 'E-pin service for selected network and amount not available at the moment.' : count($total_created) . ' airtime pin was generated.';

                return response()->json(
                    array(
                        'message' => $message
                    ),
                    200
                );
            }
            
            return response()->json(
                array(
                    'message' => 'E-pin generated successfully.'
                ),
                200
            );

        } catch (\Exception $e) {
            return response()->json(array(
                'message' => 'An error occurred',
                'short_description' => $e->getMessage() //. $e->getLine()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }

    }
    
    
    public function userGetEPinPDF(Request $request)
    {
        try{
            
            //$downloadEPinCommand = $request->input('epin_download');download input button or link.
            return Storage::download('epin.pdf', 'PurchasedEpin');
            
            //delete the storage:
            Storage::delete('epin.pdf');
            
        } catch (\Exception $ex) {
            
            return response()->json([
                'message'=>'Error in retrieving epin pdf files!'
            ], 500);
            
        }
    }
    


    public function notification(Request $request)
    {
        $status = array();
        
        $user_id = $request->user()['id'];
        
        $notificationModel = Notification::where([
            'uid'=> $user_id,
            'status' => 'un-read'        
        ]);
        
        $status['notification_count'] = $notificationModel->count();
        $status['notification_last_5'] = $notificationModel->orderBy('created_at', 'DESC')->take(8)->get();
        
        //get header response due to CORS warning:
        return response()->json($status, 200)->header("Access-Control-Allow-Origin",  "*");
        
    }

    public function readNotification(Request $request)
    {
        Notification::where('id', $request->input('id'))->update([
            'status' => 'read'
        ]);
        return response()->json([
            'message' => 'notification read'
        ], 200);
    }

    public function allNotification(Request $request)
    {
        $user_id = $request->user()['id'];
       
        $notification = Notification::where('uid', $user_id)->where('status', 'un-read')->get();
        for ($i = 0; $i < count($notification); $i++) {
            Notification::where('id', $notification[$i]['id'])->update([
                'status' => 'read'
            ]);
        }

        $notification = Notification::where('uid', $user_id)->orderBy('created_at', 'DESC')->paginate(30);

        return response()->json(
            array(
                'message' => 'All notification',
                'data' => [
                    'notification' => $notification
                ]
            ),
            200
        );
    }

    public function fetchUserUpLineAndDownLine(Request $request)
    {

        // calculating downline with respect to user they have
        $downLine = [];

        //$referral_id = $request->user()['referral_id'];
        $username = $request->user()['username'];
        
        // fetch_direct down line
        //$fetch_user_down_line = User::withCount('referredUser')->where('referral', $referral_id)->get();
        $fetch_user_down_line = User::withCount('referredUser')->where('username', $username)->get();

        return response()->json(
            array(
                'direct_down_line' => $fetch_user_down_line
            ),
            200
        );
    }

    public function fetchInDirectDownLine(Request $request)
    {
        //$referral_id = $request->input('referral_id');
        $username = $request->user()['username'];
        
        //$fetch_user_down_line = User::withCount('referredUser')->where('referral', $referral_id)->get();
        $fetch_user_down_line = User::withCount('referredUser')->where('username', $username)->get();
        
        return response()->json(
            array(
                'in_direct_down_line' => $fetch_user_down_line
            ),
            200
        );
    }
}
