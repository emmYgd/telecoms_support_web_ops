<?php

namespace App\Http\Controllers\Service;

use App\Events\UserDownLine;

use App\Coin;
use App\Log;
use App\MembershipPlan;
use App\Setting;
use App\User;
use App\UserPin;
use App\Wallet;

use Illuminate\Support\Str;

class General
{
    //converts the nscoin to real money:
    public static function convertNScoinToRealMoney($userObject){
        
        //get Wallet:
        $walletModel = Wallet::where(['wallet_id'=>$userObject->id])->first();
        
        //get Coin:
        $coinModel = Coin::where(['uid'=>$userObject->id])->first();
        
        /*if (($walletModel->count()>1) || ($coinModel->count>1){
        }*/
        
        //get Wallet values:
        $previousWalletBalance = $walletModel->previous_balance;
        $currentWalletBalance = $walletModel->current_balance;
        
        //get Coin values:
        $currentCoinBalance = $coinModel->ns_coin;
        
        
        //set latest Wallet balance:
        $latestPreviousWalletBalance = $currentWalletBalance;
        $latestCurrentWalletBalance = $currentWalletBalance + $currentCoinBalance/2;
        
        //set latest Coin balance:
        $latestCoinBalance = 0; 
        
        
        //update these respectively:
        $walletModel->update([
            'previous_balance'=>$latestPreviousWalletBalance,
            'current_balance'=>$latestCurrentWalletBalance
        ]);
        
        $coinModel->update(['ns_coin'=>$latestCoinBalance]);
    }
    
    
    /**
     * @param $referral_id : user referring new user
     * @param $referral : user been referred
     * @param $app_code
     */
    //public static function creditReferral($referral_id, $referral, $app_code)
    public static function creditReferral($referral_id, $referral, $app_code)
    {
        if ($referral_id == 'admin')
            return;

        // fetch user details
        $user_data = User::where(['referral_id' => $referral_id])->first();

        // check if user has reached max referral
        $settings = Setting::first();

        $user_referral_count = User::where(['referral' => $referral_id])->count();

        if ($app_code !== 'NS') {

            // fetch wallet info
            $wallet_info = Wallet::where(['wallet_id' => $user_data->wallet_id])->first();

            // update wallet balance
            $update_wallet_balance = $wallet_info->current_balance + $settings->referral_bonus_amount;

            Wallet::where(['wallet_id' => $user_data->wallet_id])->update([

                'previous_balance' => $wallet_info->previous_balance,

                'current_balance' => $update_wallet_balance
            ]);
        } else{ 
            self::calculateReferralOverSpill($referral_id, $referral);
        }
    }

    /**
     * @param $referral_id : user referring new user
     * @param $referred
     */
    public static function calculateReferralOverSpill($referral_id, $referred)
    {

        // check if user has reached max referral
        $settings = Setting::first();

        // fetch user referring new user referrals
        $referral_user_count = User::where(['referral' => $referral_id])->count();

        if ($referral_user_count < $settings->max_referral) {
            // update the referred account
            User::where(['referral_id' => $referred])->update([
                'referral' => $referral_id
            ]);
        }

        if ($referral_user_count == $settings->max_referral) {

            // spill new referred to down line having pending fill
            event(new UserDownLine($referral_id, $referred));

        }

    }

    /**
     * @param $uid
     * @param $description
     */
    public static function logActivities($uid, $description)
    {
        Log::create([
            'description' => $description,
            'uid' => $uid,
            'ip_address' => request()->ip()
        ]);

        NotificationHandler::saveNotification($uid, $description);
    }

    /**
     * @param $uid
     * @param $coin
     */
    public static function logCoinsGenerated($uid, $coin)
    {
        Coin::create([
            'uid' => $uid,
            'ns_coin' => $coin
        ]);
    }

    /**
     * @param $pin
     * @param $uid
     * @return string[]
     */
    public static function verifyTransactionPin($pin, $uid)
    {
        //password_verify(a,b) -> checks wheather the hash of supplied value a equals provided hash value b ... 
        $verify_pass = password_verify($pin, UserPin::where(['uid' => $uid])->first()['pin']);
        
        $returnParams = [];
        
        if (!$verify_pass) {
            
            $returnParams =  [
                'status' => 'failed', 
                'message' => 'Invalid transaction pin provided'
            ];
            
        }else{
            $returnParams = [
                'status' => 'success'
            ];
        }

        return $returnParams;
    }

    public static function httpErrorCode()
    {
        return [400, 401, 402, 500];
    }

    public static function listBanksStatic()
    {
        return json_decode('[
  {
    "Id": 1,
    "Code": "044",
    "Name": "Access Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 2,
    "Code": "023",
    "Name": "Citi Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 4,
    "Code": "050",
    "Name": "EcoBank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 5,
    "Code": "011",
    "Name": "First Bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 6,
    "Code": "214",
    "Name": "First City Monument Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 7,
    "Code": "070",
    "Name": "Fidelity Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 8,
    "Code": "058",
    "Name": "Guaranty Trust Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 9,
    "Code": "076",
    "Name": "Polaris bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 10,
    "Code": "221",
    "Name": "Stanbic IBTC Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 11,
    "Code": "068",
    "Name": "Standard Chaterted bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 12,
    "Code": "232",
    "Name": "Sterling Bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 13,
    "Code": "033",
    "Name": "United Bank for Africa",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 14,
    "Code": "032",
    "Name": "Union Bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 15,
    "Code": "035",
    "Name": "Wema Bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 16,
    "Code": "057",
    "Name": "Zenith bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 17,
    "Code": "215",
    "Name": "Unity Bank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 18,
    "Code": "101",
    "Name": "ProvidusBank PLC",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 183,
    "Code": "082",
    "Name": "Keystone Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 184,
    "Code": "301",
    "Name": "Jaiz Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 186,
    "Code": "030",
    "Name": "Heritage Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 231,
    "Code": "100",
    "Name": "Suntrust Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 252,
    "Code": "608",
    "Name": "FINATRUST MICROFINANCE BANK",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 253,
    "Code": "090175",
    "Name": "Rubies Microfinance Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 254,
    "Code": "090267",
    "Name": "Kuda",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 258,
    "Code": "090115",
    "Name": "TCF MFB",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 259,
    "Code": "400001",
    "Name": "FSDH Merchant Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 260,
    "Code": "502",
    "Name": "Rand merchant Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 301,
    "Code": "103",
    "Name": "Globus Bank",
    "IsMobileVerified": null,
    "branches": null
  },
  {
      "Id": 389,
    "Code": "327",
    "Name": "Paga",
    "IsMobileVerified": null,
    "branches": null
  }
]', true);
    }

    public static function filterBankProvided($bank_name)
    {

        for ($i = 0; $i < count(self::listBanksStatic()); $i++) {

            if (Str::lower(self::listBanksStatic()[$i]['Name']) == Str::lower($bank_name)) {
                return self::listBanksStatic()[$i];
            }

        }

    }

    public static function fetchLevel($level)
    {
        $membership_level = MembershipPlan::get();

        for ($i = 0; $i < count($membership_level); $i++) {

            if (Str::lower($level) == Str::lower($membership_level[$i]['name'])) {

                return $membership_level[$i];

            }
        }
    }
}
