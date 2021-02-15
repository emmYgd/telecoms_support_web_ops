<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Service\Funding;
use App\Http\Controllers\Service\General;

use App\Mail\ActivationMail;
use App\Mail\ForgotPassword as forgot_password_mail;
use App\Mail\WelcomeMail;

use App\ForgotPassword;
use App\Setting;
use App\Staff;
use App\TwoFactor;
use App\User;
use App\Coin;
use App\Wallet;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Authentication extends Controller
{
    public $external_site = 'https://app.naijasub.com/verification/';

    public $app_code = 'NS';

    public function login(Request $request)
    {
        $status = array();
        
        try {
            
            //normally, we could have written it this way:
            /*
                $request->validate([
                    "email" => "required",
                    "password" => "required"
                ]);
            */
            
            //extracted because we will still be using it later in our Model query:enhanced to accept both user email or username: 
            //$emailOrUsername = $request->input('email_username');
            $emailOrUsername = $request->input('email');
            $password = $request->input('password');

            $rules = [
                //'email_username' => 'required',
                'name' => 'required',
                'password' => 'required'
            ];
            
            //validate with the above rules:
            $validation = Validator::make($request->all(), $rules);
            
            
            $errors = $validation->errors();
            //use switch/case structure for check: 
            switch($errors){
                
                case $errors->has('email') : 
                    throw new \Exception('Email address is required', 400);
                    break;
                    
                case $errors->has('password') :
                    throw new \Exception('Password is required', 400);
                    break;
            }

            /*if ($errors->has('email')){
                throw new \Exception('Email address is required', 400);
            }

            if ($errors->has('password')){
                throw new \Exception('Password is required', 400);
            }*/
            
            $userModel;
            
            //Query Db here:
            //get User where email == email supplied from request:
            $userModel = User::where(['email' => $emailOrUsername])->first();
            
            if (empty($userModel)){
                //if the query fails then:check for username column in db:
                $userModel = User::where(['username' => $emailOrUsername])->first();
                if(empty($userModel)){
                    throw new \Exception('Invalid Login attempt, pls supply correct login tokens');
                }
            }
            
                //verify password:
                if (password_verify($password, $userModel->password)) {

                    $status['code'] = 1;

                    $status['data']['accessToken'] = $userModel->createToken('User access token', ['user'])->accessToken;

                    $status['data']['user'] = $userModel;

                    $status['message'] = 'success';

                    $status['short_description'] = 'Access granted';

                } else {
                    throw new \Exception('Invalid credentials provided', 400);
                }
                
                return response()->json($status, 200);

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage(); //'Login error. Please retry again!'; 
            
            
            $code = 500;

            if (in_array($e->getCode(), $this->httpErrorCode())) {

                $code = $e->getCode();

            }

            return response()->json($status, $code);
        }

    }

    public function register(Request $request)
    {

        $status = array();

        try {

            // register new users

            //$name = $request->input('name');
            //accept first name and last name from user, to be inputed inside the database:
            $first_name = $request->input('first_name');
            
            $last_name = $request->input('last_name');

            $email = $request->input('email');
            
            $state_of_residence = $request->input('state_of_residence');//insert this to the front..
            
            $username = $request->input('username');//insert this to the front..
            
            $password = $request->input('password');

            $phone = $request->input('phone');

            $confirmPassword = $request->input('confirm_password');
            
            //This has been changed to username henceforth:
            $referral_upline_username = $request->input('referral');
            
            
            //set rules and validate:
            $rules = array(
                //'name' =>'Required',
                'first_name' => 'Required',
                'last_name' => 'Required',
                'email' => 'Required|unique:users',
                'state_of_residence' => 'nullable',
                'username' => 'Required|unique:users',
                'phone' => 'Required|unique:users',
                'password' => 'Required|unique:users',
                'confirmPassword' =>'Required'
            ); 
            
            $validation = Validator::make($request->all(), $rules);

            $errors = $validation->errors();

            if ($validation->failed()){
                throw new  \Exception($errors->first(), 400);
            }

            //validate password:
            if($confirmPassword !== $password){
                throw new \Exception('The Password and Confirm Password inputs must be equal!', 400);
            }
            
            //validate referral
            if (!empty($referral_upline_username) && $referral_upline_username !== 'admin') {

                //if (User::where(['referral_id' => $referral])->count() == 0){
                if (User::where(['username' => $referral_upline_username])->count() == 0){
                    throw new \Exception('Invalid Referral Upline Username provided! Contact your referral to get the their correct username.', 400);
                }
            }
            
            //generate a new wallet id:
            $wallet_id = '901' . mt_rand(100000, 999999);
            
            //ensure this newly generated wallet id is not the same as any previously generated walled id:  
            while (User::where(['wallet_id' => $wallet_id])->count() > 0) {
                $wallet_id = '901' . mt_rand(100000, 999999);
            }

            $email_verify = time() . ':' . bin2hex(random_bytes(32));

            //$referral_new_user_id = strtoupper(Str::random(10));
            
            //ensure this newly generated referal id is not the same as any previously generated referal id:  
            /*while (User::where(['referral_id' => $referral_new_user_id])->count() > 0) {
                $referral_new_user_id = strtoupper(Str::random(10));
            }*/
            
            //insert these into users: 
            //note this: (Users::create([])) won't work...
            $user = new User();

            //$user->name = $name;
            
            $user->first_name = $first_name;
            
            $user->last_name = $last_name;
            
            $user->email = $email;
            
            $user->state_of_residence = $state_of_residence;
            
            $user->username = $username;

            $user->phone = $phone;

            $user->wallet_id = $wallet_id;

            $user->email_verified_token = $email_verify;

            $user->email_verified_status = 'pending';

            $user->password = password_hash($password, PASSWORD_DEFAULT);

            $user->account_type = 'user';

            $user['2fa'] = 'on';

            //$user->referral_id = $referral_new_user_id;

            $user->referral_upline_username = $referral_upline_username;
            $user->bvn_verify = 0;

            $user->save();//user id is generated automatically on save...
            
            //now assign a new providus account to this User:
            $funding = new Funding();

            $funding->providusAssignUserAccount($user->id);

            // check if registration bonus is allowed:
            /*$settings = Setting::first();

            $registration_bonus = 0;

            if ($settings->registration_bonus_status === 'on') {
                $registration_bonus = $settings->registration_bonus_amount;
            }*/

            Wallet::create([

                'wallet_id' => $wallet_id,

                'previous_balance' => 0,

                'current_balance' => 0//$registration_bonus

            ]);
            
            //New System -- No bonus is to be credited to the upline immediately after downline bonus...
            
            //credit the upline referral:
            //General::creditReferral($referral, $referral_new_user_id, $this->app_code);
            
            //General::creditReferral($referral_upline_username, $username, $this->app_code);
            
            
            //Mail to user email:
            Mail::to($email)->send(new WelcomeMail($name));

            $token = $this->external_site . $email_verify;

            Mail::to($email)->send(new ActivationMail($name, $token));

            $status['data']['user'] = $user;

            $status['code'] = 1;

            $status['message'] = !empty($referral_username) ? "Success! {$referral_username} referred you" : 'Success! no one referred you';

            $status['short_description'] = 'Successfully created account. Click on the verification link sent to the supplied email to activate your account';

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();
            //'Signup error. Please retry again!'

            return response()->json($status, 400);
        }

        return response()->json($status, 200);

    }
    
    //To reset password, users need to first provide their gmail address and have a reset link sent to their gmail.
    public function forgotPassword(Request $request)
    {
        $status = array();

        try {

            $email = $request->input('email');

            if (empty($email)){
                throw new \Exception('Email address is required', 400);
            }

            // check if email exist
            if (User::where(['email' => $email])->count() == 0){
                throw new \Exception('Email address provided is not a valid email address', 400);
            }

            $user = User::where(['email' => $email])->first();

            $token = bin2hex(random_bytes(32));

            ForgotPassword::create([

                'uid' => $user->id,

                'token' => $token,

                'status' => 'unused'
            ]);

            $token = $this->external_site . $token;

            Mail::to($email)->send(new forgot_password_mail($token, $user['name']));

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'Password Change link successfully sent to your provided e-mail. Click on the link to continue!';

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();/*'Password Change link not sent your provided gmail! Please try again.';*/

            $code = 500;

            if (in_array($e->getCode(), $this->httpErrorCode())) {
                $code = $e->getCode();
            }

            return response()->json($status, $code);
        }

        return response()->json($status, 200);

    }
    
    //after clicking on the reset link in the gmail, users are then asked to supply the new password:
    public function resetPassword(Request $request)
    {

        $status = array();

        try {

            $password = $request->input('password');

            $confirm_password = $request->input('confirm_password');
            
            //this should be supplied by the client program
            $token = $request->input('token');

            if (empty($password)){
                throw new \Exception('Password is required', 400);
            }

            if (empty($confirm_password)){
                throw new \Exception("Confirm password is required", 400);
            }

            if ($password !== $confirm_password){
                throw new \Exception('Password does not match', 400);
            }
            
            if (empty($token)){
                throw new \Exception('Token is required', 400);
            }

            if (ForgotPassword::where(['token' => $token, 'status' => 'unused'])->count() == 0){
                throw new \Exception('Password token not valid', 400);
            }
            
            $tokenInfo = ForgotPassword::where(['token' => $token])->first();

            if (User::where(['id' => $tokenInfo->uid])->count() == 0){
                throw new \Exception('Authentication failed', 401);
            }
            
            //Update the password hash:
            User::where(['id' => $tokenInfo->uid])->update([
                'password' => password_hash($password, PASSWORD_DEFAULT)
                //bcrypt($password)
            ]);
            
            //use entity relations to model this data inside the User Model later when revisiting this codebase:
            ForgotPassword::where(['token' => $token, 'status' => 'unused'])->update([
                'status' => 'used'
            ]);

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'Password has been reset successfully';

        } catch (\Exception $e) {
            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();
                                /*"Password Reset error. Please try again!"*/
            $code = 500;

            if (in_array($e->getCode(), $this->httpErrorCode())) {
                $code = $e->getCode();
            }

            return response()->json($status, $code);
        }

        return response()->json($status, 200);

    }

    public function twoFactorAuth(Request $request)
    {
        $status = array();

        try {

            $code = $request->input('code');

            $uid = $request->input('uid');

            // check if code exist and not used

            if (TwoFactor::where(['code' => $code, 'uid' => $uid, 'status' => 'unused'])->count() == 0){
                throw new \Exception('Authentication failed', 400);
            }

            $authDetails = TwoFactor::where(['code' => $code, 'uid' => $uid])->first();

            $userInfo = User::where(['id' => $authDetails->uid])->first();

            TwoFactor::where(['code' => $code, 'status' => 'unused'])->update([
                'status' => 'used'
            ]);

            $status['code'] = 1;

            $status['data']['accessToken'] = $userInfo->createToken('User access token', ['user'])->accessToken;

            $status['data']['user'] = $userInfo;

            $status['message'] = 'success';

            $status['short_description'] = 'Access granted';

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            $code = 500;

            if (in_array($e->getCode(), $this->httpErrorCode())) {

                $code = $e->getCode();

            }

            return response()->json($status, $code);
        }

        return response()->json($status, 200);
    }

    public function verifyAccount(Request $request)
    {

        $status = array();

        try {

            $token = $request->input('token');

            // check if token is not empty
            if (empty($token)){
                throw new \Exception('Verification token is required', 400);
            }

            // check if token exists:
            $userModel = User::where([
                'email_verified_token' => $token, 
                'email_verified_status' => 'pending'
                ]);//->count();
                
            if ( $userModel->count() == 0){
                throw new \Exception('Invalid verification token provided', 400);
            }
            
            //assign params:
            $user_info = $userModel->first();
            
            //update Model:
            $userModel->update([
                'email_verified_status' => 'active',
                'email_verified_at' => new \DateTime(),
                'email_verified_token' => 'used'
            ]);

            // credit user if app_code = ns
            
            //$walletModel =  Wallet::where(['wallet_id' => $user_info->wallet_id]);
            
            /*$wallet_info = $walletModel->first();

                $update_wallet_balance = $wallet_info->current_balance + ($bonus_amount / 2);

                $wallet->update([
                    'previous_balance' => $wallet_info->current_balance,
                    'current_balance' => $update_wallet_balance
                ]);*/
            
            // credit user if app_code = ns:
            if ($this->app_code == 'NS') {
                
                $bonus_coin = 100;
                
                $coinModel = Coin::create([
                    'uid' => $userModel->id,
                    'ns_coin' => $bonus_coin
                ]);
                
                //check the general out later:
                General::logCoinsGenerated($user_info->id, 100);

                General::logActivities($user_info->id, 'Account was credited with 100NS coin bonus, redeemable at 50NGN when you fund your account.');
            }

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'Account verified successfully.'. 'There\'s a pending 100 NS coin bonus redeemable at 50NGN when you fund your account wallet.';

        } catch (\Exception $e) {
            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            $code = 500;

            if (in_array($e->getCode(), $this->httpErrorCode())) {

                $code = $e->getCode();

            }

            return response()->json($status, $code);
        }

        return response()->json($status, 200);

    }

    public function staffLogin(Request $request)
    {
        $status = array();

        try {


            $email = $request->input('email');

            $password = $request->input('password');

            $rules = array(
                'email' => 'Required',
                'password' => 'Required'
            );

            $validation = Validator::make($request->all(), $rules);

            $errors = $validation->errors();

            if ($validation->failed())
                throw new  \Exception($errors->first(), 400);

            $staff = staff::where(['email' => $email])->first();

            if (!$staff)
                throw new \Exception('Invalid credential provided');

            if (password_verify($password, $staff->password)) {

                $status['code'] = 1;

                $status['data']['accessToken'] = $staff->createToken('Personal access', ['admin'])->accessToken;

                $status['data']['staff_details'] = $staff;

                $status['message'] = 'success';

                $status['short_description'] = 'Access granted';

            } else {
                throw new \Exception('Invalid credentials provided', 401);
            }

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            return response()->json($status, 400);
        }

        return response()->json($status, 200);

    }

    public function httpErrorCode()
    {
        return [400, 401, 402, 500];
    }
}
