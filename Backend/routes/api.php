<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Check out the config/auth.php for 'auth:api' guard set as middleware() here:(check the app/Provider/AuthProvider too)
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('cache', function(){
    Artisan::call('config:cache');
    shell_exec('php ../artisan passport:install');
});

//collect the route api here...

//auth/xxx routes:
Route::prefix('auth')->group(function () {

    Route::post('login', 'Authentication@login');

    Route::post('register', 'Authentication@register');

    Route::post('forgot-password', 'Authentication@forgotPassword');

    Route::post('reset-password', 'Authentication@resetPassword');

    Route::post('two-factor-auth', 'Authentication@twoFactorAuth');

    Route::post('verify-account', 'Authentication@verifyAccount');

    Route::post('staff-login', 'Authentication@staffLogin');

});

//web-hook/xxx routes for external APIs:
Route::group(['prefix' => 'web-hook'], function () {
    
    // another layer of security is required
    Route::post('providus-account-funding', 'WebHookController@providusAccountFunding');

    //live  btc rate
    Route::post('live-btc-conversion', 'WebHookController@liveBTCConversion');
});


//Handle this just in case the request comes as a GET verb:
//This is suspiscious as login should be routed through an aiuthenticated post route above and not ordinarily here... 
//Route::get('login', 'Authentication@login')->name('login');
//but we know that most logins are post requests: -- 

//logged in users with path -> //v1/xxx: - Scope is registered at 
//App/Providers/AuthServiceProvider and implemented at App/Http/Middleware/CheckForAllScopes
//Middleware here is subjected to two Guards in config/auth.php - 'auth:user' and 'scopes:user'
Route::group(['middleware' => ['auth:user', 'scopes:user'], 'prefix' => 'v1'], function () {

    Route::post('fetch-dashboard', 'UserController@fetchDashboard');

    Route::post('fund-methods', 'UserController@fundMethods');

    Route::post('fund-wallet', 'UserController@fundWallet');

    Route::post('fetch-funding', 'UserController@fetchFunding');

    Route::post('create-transaction-pin', 'UserController@createTransactionPin');

    Route::put('update-transaction-pin', 'UserController@updateTransactionPin');

    Route::post('upgrade-membership', 'UserController@upgradeMembership');

    Route::post('fetch-services', 'UserController@fetchServices');

    Route::post('fetch-sub-services', 'UserController@fetchSubServices');

    Route::post('fetch-packages', 'UserController@fetchPackages');

    Route::post('init-transaction', 'UserController@initiatePurchase');

    Route::post('purchase-airtime', 'UserController@purchaseAirtime');

    Route::post('sell-airtime', 'UserController@sellAirtime');

    Route::post('purchase-data', 'UserController@purchaseData');
    
    Route::post('purchase-internet-service', 'UserController@purchaseInternet');

    Route::post('purchase-cable-service', 'UserController@purchaseCableService');

    Route::post('purchase-electricity-service', 'UserController@purchaseElectricityService');

    Route::post('confirm-coin', 'UserController@confirmCoin');

    Route::post('profile', 'UserController@profile');

    Route::post('verify-bvn', 'UserController@verifyAccountUsingBvn');

    Route::post('update-profile', 'UserController@updateProfile');

    Route::post('update-password', 'UserController@updatePassword');

    Route::post('update-bank-information', 'UserController@updateBankInformation');

    Route::post('withdraw', 'UserController@withdraw');

    Route::post('make-withdraw', 'UserController@makeWithdrawal');

    Route::post('transactions', 'UserController@transactions');

    Route::post('transactions-details', 'UserController@transactionDetails');

    Route::post('subscribe-epin', 'UserController@subscribeEpin');

    Route::post('purchase-e-pin', 'UserController@purchaseEPin');
    
    Route::post('download-e-pin', 'UserController@userGetEPinPDF');

    Route::post('verify-wallet-user', 'UserController@verifyWalletUser');

    Route::post('sell-coin', 'UserController@sellCoin');

    Route::post('buy-coin', 'UserController@buyCoin');

    Route::post('notification', 'UserController@notification');

    Route::post('down-line', 'UserController@fetchUserUpLineAndDownLine');

    Route::post('in-direct-down-line', 'UserController@fetchInDirectDownLine');

    Route::post('read-notification', 'UserController@readNotification');

    Route::post('all-notification', 'UserController@allNotification');
});


//logged in admin with path -> //staff/xxx: - Scope is registered at 
//App/Providers/AuthServiceProvider and implemented at App/Http/Middleware/CheckForAllScopes

//Middleware here is subjected to two Guards in config/auth.php - 'auth:admin' and 'scopes:admin':
Route::group(['middleware' => ['auth:admin', 'scopes:admin'], 'prefix' => 'staff'], function () {

    Route::post('preview', 'AdminController@preview');

    Route::post('create-staff', 'AdminController@createStaff');

    Route::post('set-up', 'AdminController@setUp');

    Route::post('fetch-users', 'AdminController@fetchUsers');

    Route::post('ban-user', 'AdminController@banUser');

    Route::post('delete-user', 'AdminController@deleteUser');

    Route::post('user-details', 'AdminController@userDetails');

    Route::post('fetch-transactions', 'AdminController@fetchTransactions');

    Route::post('re-query-transaction', 'AdminController@reQueryTransaction');

    Route::post('update-transaction-status', 'AdminController@updateTransactionStatus');

    Route::post('fetch-funding', 'AdminController@fetchFunding');

    Route::post('fetch-transaction-details', 'AdminController@fetchTransactionDetails');

    Route::post('manuel-funding', 'AdminController@manualFunding');

    Route::post('funding-with-wallet-id', 'AdminController@fundingWithWalletId');

    Route::post('debit-with-wallet-id', 'AdminController@debitWithWalletId');

    Route::post('services', 'AdminController@services');

    Route::post('disable-service', 'AdminController@disableService');

    Route::post('activate-service', 'AdminController@activateService');

    Route::post('disable-sub-service', 'AdminController@disableSubService');

    Route::post('activate-sub-service', 'AdminController@activateSubService');

    Route::post('set-minimum-amount', 'AdminController@setMinimumAmount');

    Route::post('fetch-external-service', 'AdminController@fetchExternalService');

    Route::post('save-packages-data', 'AdminController@savePackagesData');
    
    Route::post('edit-data-vendor', 'AdminController@editDataVendor');

    Route::post('fetch-membership-plan', 'AdminController@fetchMembershipPlan');

    Route::post('manuel-banks', 'AdminController@manuelBanking');

    Route::post('create-bank', 'AdminController@createBanking');

    Route::post('edit-bank', 'AdminController@editBanks');

    Route::post('delete-bank', 'AdminController@deleteBank');

    Route::post('fetch-staff', 'AdminController@fetchStaff');

    Route::post('update-staff-password', 'AdminController@updatePassword');

    Route::post('edit-staff', 'AdminController@editStaff');

    Route::post('delete-staff', 'AdminController@deleteStaff');

    Route::post('create-e-pin', 'AdminController@createEPin');

    Route::post('update-e-pin', 'AdminController@updateEPin');

    Route::post('delete-e-pin', 'AdminController@deleteEPin');

    Route::post('fetch-e-pin', 'AdminController@fetchEPin');
    
    Route::post('mlm', 'AdminController@fetchMlmUser');
    Route::post('fetch-downline', 'AdminController@fetchDownLine');
    Route::post('swap-up-line', 'AdminController@swapUpLine');
        Route::post('settings', 'AdminController@settings');
        
    Route::post('update-dollar-selling-rate', 'AdminController@updateDollarSellingRate');
    Route::post('update-dollar-buying-rate', 'AdminController@updateDollarBuyingRate');
    
     Route::post('admin-notification', 'AdminController@adminNotification');
    Route::post('delete-notification', 'AdminController@deleteNotification');
    Route::post('create-notification', 'AdminController@createNotification');
    Route::post('update-notification-status', 'AdminController@updateNotificationStatus');
    Route::post('update-notification', 'AdminController@updateNotification');
    Route::post('update-membership-plan', 'AdminController@updateMembership');

});
