<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Service\Funding;
use App\Http\Controllers\Service\General;

use App\AdminBanks;
use App\Admin;
use App\Card;
use App\Coin;
use App\Epin;
use App\FundingCharge;
use App\Log;
use App\MembershipPlan;
use App\Packages;
use App\PaymentLog;
use App\Services;
use App\Staff;
use App\SubServices;
use App\Transaction;
use App\User;
use App\Wallet;
use App\Setting;
use App\Notification;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AdminController extends Controller
{

    public function setUp()
    {
        return [
            'user' => [
                ['name' => 'Full Name', 'key' => 'name'],
                ['name' => 'Phone Number', 'key' => 'phone'],
                ['name' => 'Email Address', 'key' => 'email'],
                ['name' => 'Membership Level', 'key' => 'membership_level'],
            ],
            'transaction_type_filter' => [
                ['name' => 'Manuel Funding', 'key' => 'manuel_funding'],
                ['name' => 'Gateway Funding', 'key' => 'gateway_funding'],
                ['name' => 'Bank Funding', 'key' => 'bank_funding'],
                ['name' => 'Transfer Funding', 'key' => 'transfer_funding'],
                ['name' => 'Airtime Service', 'key' => 'airtime'],
                ['name' => 'Data Service', 'key' => 'data'],
                ['name' => 'Cable Service', 'key' => 'cable'],
                ['name' => 'Electricity Service', 'key' => 'electricity'],
                ['name' => 'Coin Service', 'key' => 'coin'],
            ],
            'transaction_search_filter' => [
                ['name' => 'Reference', 'key' => 'reference'],
                ['name' => 'Payment Reference', 'key' => 'payment_reference'],
                ['name' => 'Transaction Type', 'key' => 'transaction_type'],

            ],
            'service_packages' => [
                'data',
                'cable'
            ]
        ];
    }

    public function preview(Request $request)
    {
        try {

            return response()->json(
                array(
                    'message' => 'dashboard',
                    'data' => [
                        'recent_transaction' => Transaction::with('userDetails')->orderBy('created_at', 'DESC')->take(6)->get(),
                        'transaction' => [
                            'today' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->count(),
                            'week' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->count(),
                            'month' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfMonth())->where('created_at', '<', Carbon::now()->endOfMonth())->count(),
                            'year' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfYear())->where('created_at', '<', Carbon::now()->endOfYear())->count()
                        ],
                        'sales' => [
                            'today' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount'),
                            'week' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->sum('amount'),
                            'month' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfMonth())->where('created_at', '<', Carbon::now()->endOfMonth())->sum('amount'),
                            'year' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfYear())->where('created_at', '<', Carbon::now()->endOfYear())->sum('amount')
                        ],
                        'coin' => [
                            'today' => Coin::where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->sum('ns_coin'),
                            'week' => Coin::where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->sum('ns_coin'),
                            'month' => Coin::where('created_at', '>', Carbon::now()->startOfMonth())->where('created_at', '<', Carbon::now()->endOfMonth())->sum('ns_coin'),
                            'year' => Coin::where('created_at', '>', Carbon::now()->startOfYear())->where('created_at', '<', Carbon::now()->endOfYear())->sum('ns_coin')
                        ],
                        'users' => [
                            'today' => User::where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->count(),
                            'week' => User::where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->count(),
                            'month' => User::where('created_at', '>', Carbon::now()->startOfMonth())->where('created_at', '<', Carbon::now()->endOfMonth())->count(),
                            'year' => User::where('created_at', '>', Carbon::now()->startOfYear())->where('created_at', '<', Carbon::now()->endOfYear())->count()
                        ]
                    ],

                ),
                200
            );

        } catch (\Exception $e) {
            return response()->json(
                array(
                    'message' => 'An error occurred',
                    'short_description' => $e->getMessage()
                ),
                400
            );
        }
    }

    public function fetchUsers(Request $request)
    {
        $result = array();

        try {

            // filter user details been passed terms and reference can't be empty
            $terms = $request->input('terms');

            $field = $request->input('field');

            $start_date = $request->input('start_now');

            $end_date = $request->input('end_now');

            $status = $request->input('status');

            $user = '';

            if (!empty($terms) && !empty($end_date) && !empty($start_date) && !empty($status)) {
                $user = User::where($field, 'like', "%$terms%")->where('created_at', '<', $end_date)->where('status', $status)->where('created_at', '>', $start_date)->orderBy('created_at', 'DESC')->paginate(30);
            } else if (!empty($terms) && empty($start_date) && empty($end_date) && empty($status)) {
                $user = User::where($field, 'like', "%$terms%")->orderBy('created_at', 'DESC')->paginate(30);
            } else if (empty($terms) && !empty($start_date) && !empty($end_date) && empty($status)) {
                $user = User::where('created_at', '<', $end_date)->where('created_at', '>', $start_date)->orderBy('created_at', 'DESC')->paginate(30);
            } else if (empty($terms) && empty($start_date) && empty($end_date) && !empty($status)) {
                $user = User::where('status', $status)->orderBy('created_at', 'DESC')->paginate(30);
            } else {
                $user = User::orderBy('created_at', 'DESC')->paginate(30);
            }

            return response()->json(
                array(
                    'message' => 'users',
                    'data' => [
                        'user' => $user,
                        'dropdown' => $this->setUp()['user'],
                        'total_user' => User::count(),
                        'today_user' => User::where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->count(),
                        'reseller' => User::where('membership_level', 're-seller')->where('status', 'active')->count(),
                        'starter' => User::where('membership_level', 'starter')->where('status', 'active')->count()
                    ]
                ), 200);

        } catch (\Exception $e) {

            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }
    }

    public function banUser(Request $request)
    {

        try {

            $uid = $request->input('uid');

            $reason = $request->input('reason');

            $rule = array(
                'uid' => 'Required',
                'reason' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            if ($validator->failed())
                throw new \Exception($validator->errors()->first(), 400);

            $description = 'Banned by ' . $request->user()->name . '-reason: ' . $reason;

            General::logActivities($uid, $description);

            User::where('id', $uid)->update([
                'status' => 'ban'
            ]);

            return response()->json(array(
                'message' => 'user banned'
            ), 200);

        } catch (\Exception $e) {

            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }

    public function deleteUser(Request $request)
    {
        try {

            $uid = $request->input('uid');

            $rule = array(
                'uid' => 'Required',
            );

            $validator = Validator::make($request->all(), $rule);

            if ($validator->failed())
                throw new \Exception($validator->errors()->first(), 400);

            User::where('id', $uid)->delete();

            return response()->json(array(
                'message' => 'user deleted'
            ), 200);

        } catch (\Exception $e) {

            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }

    public function userDetails(Request $request)
    {

        try {

            $uid = $request->input('uid');

            $rule = array(
                'uid' => 'Required',
            );

            $validator = Validator::make($request->all(), $rule);

            $errors = $validator->errors();

            if ($validator->failed()) {
                throw new \Exception($errors->first(), 400);
            }

            return response()->json(array(
                'message' => 'user details',
                'data' => [
                    'user' => User::where('id', $uid)->first()['user'],
                    
                    /*'first_name' => User::where('id', $uid)->first()['first_name'],
                       'middle_name' => User::where('id', $uid)->first()['middle_name'],
                       'last_name' => User::where('id', $uid)->first()['last_name'] */
                       
                    'wallets' => Wallet::where('wallet_id', User::where('id', $uid)->first()['wallet_id'])->first(),
                    'transactions' => Transaction::where('uid', $uid)->paginate(15),
                    'successful_tnx' => Transaction::where('uid', $uid)->where('status', 'success')->count(),
                    'failed_tnx' => Transaction::where('uid', $uid)->where('status', 'failed')->count(),
                    'pending_tnx' => Transaction::where('uid', $uid)->where('status', 'pending')->count(),
                    'last_tnx' => Transaction::where('uid', $uid)->orderBy('created_at', 'DESC')->first(),
                    'card' => Card::where('uid', $uid)->get(),
                    'logs' => Log::where('uid', $uid)->get(),
                    'upline' => User::where('referral_id', User::where('id', $uid)->first()['referral'])->first(),
                    'downlines' => User::where('referral', User::where('id', $uid)->first()['referral_id'])->get(),
                    'coins' => Coin::where('uid', $uid)->paginate(30),
                    'payment_logs' => PaymentLog::where('uid', $uid)->paginate(30)
                ]
            ), 200);

        } catch (\Exception $e) {

            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }

    public function fetchTransactions(Request $request)
    {

        try {

            $transaction_type = $request->input('transaction_type');

            $term = $request->input('term');

            $field = $request->input('field');

            $status = $request->input('status');

            $start_date = $request->input('start_date');

            $end_date = $request->input('end_date');

            $transactions = '';

            $transaction_count = 0;

            $total_sales = 0;

            if (!empty($transaction_type) && !empty($term) && !empty($status) && !empty($start_date) && !empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('transaction_type', $transaction_type)
                    ->where($field, 'LIKE', "%$term%")
                    ->where('status', $status)
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)
                    ->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->count();

            } else if (!empty($transaction_type) && empty($term) && empty($status) && !empty($start_date) && !empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('transaction_type', $transaction_type)
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)
                    ->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->count();

            } else if (!empty($transaction_type) && empty($term) && !empty($status) && !empty($start_date) && !empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('transaction_type', $transaction_type)
                    ->where('status', $status)
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)
                    ->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->count();

            } else if (!empty($transaction_type) && !empty($term) && !empty($status) && empty($start_date) && empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('transaction_type', $transaction_type)
                    ->where($field, 'LIKE', "%$term%")
                    ->where('status', $status)
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->count();

            } else if (!empty($transaction_type) && !empty($term) && empty($status) && empty($start_date) && empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('transaction_type', $transaction_type)
                    ->where($field, 'LIKE', "%$term%")
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->count();

            } else if (empty($transaction_type) && !empty($term) && empty($status) && empty($start_date) && empty($end_date)) {
                $transactions = Transaction::with('userDetails')
                    ->where($field, 'LIKE', "%$term%")
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->count();

            } else if (!empty($transaction_type) && empty($term) && empty($status) && empty($start_date) && empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('transaction_type', $transaction_type)
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->count();

            } else if (empty($transaction_type) && !empty($term) && !empty($status) && !empty($start_date) && !empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where($field, 'LIKE', "%$term%")
                    ->where('status', $status)
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->count();

            } else if (empty($transaction_type) && empty($term) && !empty($status) && !empty($start_date) && !empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('status', $status)
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->count();

            } else if (empty($transaction_type) && empty($term) && empty($status) && !empty($start_date) && !empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', $start_date)
                    ->where('created_at', '<', $end_date)->count();
            } else if (empty($transaction_type) && empty($term) && !empty($status) && empty($start_date) && empty($end_date)) {
                $transactions = Transaction::with('userDetails')->where('status', $status)
                    ->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('status', $status)
                    ->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('status', $status)
                    ->count();
            } else {

                $transactions = Transaction::with('userDetails')->orderBy('created_at', 'DESC')->paginate(30);

                $total_sales = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->sum('amount');

                $transaction_count = Transaction::where('status', 'success')
                    ->where('created_at', '>', Carbon::now()->startOfDay())
                    ->where('created_at', '<', Carbon::now()->endOfDay())->count();
            }

            $transaction_types = ['airtime', 'cable', 'electricity', 'data'];

            return response()->json(array(
                'message' => 'Transaction details',
                'data' => [
                    'transaction' => $transactions,
                    'transactions_count' => $transaction_count,
                    'total_sales' => $total_sales,
                    'today_sales' => Transaction::where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())
                        ->where('created_at', '<', Carbon::now()->endOfDay())->whereIn('transaction_type', $transaction_types)->count(),
                    'dropdown' => $this->setUp()['transaction_search_filter']
                ]
            ), 200);

        } catch (\Exception $e) {

            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }

    public function reQueryTransaction(Request $request)
    {

        try {

            $tid = $request->input('tid');

            $rule = array(
                'tid' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $errors = $validator->errors();

            if ($validator->failed()) {
                throw new \Exception($errors->first(), 400);
            }

            $transaction = Transaction::where('id', $tid)->first();

            if (!$transaction)
                throw new \Exception('Invalid transaction provided');

            $result = ServiceRender::reQuery($transaction);

            return response()->json(array(
                'message' => 'Re-query status',
                'data' => [
                    'response' => json_decode($result)
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));

        }

    }

    public function updateTransactionStatus(Request $request)
    {
        try {

            $tid = $request->input('tid');

            $status = $request->input('status');

            $refund = $request->input('refund');

            $rule = array(
                'tid' => 'Required',
                'status' => 'Required',
                'refund' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $errors = $validator->errors();

            if ($validator->failed()) {
                throw new \Exception($errors->first(), 400);
            }

            $transaction = Transaction::where('id', $tid)->first();

            if (!$transaction)
                throw new \Exception('Invalid transaction provided');

            // check to see the current status is same as the new status
            if ($transaction->status == $status)
                throw new \Exception('Transaction is already at ' . $status, 400);

            if ($request->input('refund')) {
                ServiceRender::refundUser($transaction->reference);
            }

            Transaction::where('id', $tid)->update([
                'status' => $status
            ]);

            return response()->json(array(
                'message' => 'Transaction status updated',
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function fetchFunding(Request $request)
    {
        try {

            $term = $request->input('term');
            $field = $request->input('field');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $status = $request->input('status');

            $funding_type = $request->input('funding_type');

            $transaction = [];
            
            
            $today_funding = 0;
            $today_pending_funding = 0;

            if ($funding_type == 'manuel_funding') {
                if(!empty($status)){
                    $transaction = Transaction::where('status', $status)->where('transaction_type', 'manual_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }else{
                    $transaction = Transaction::where('transaction_type', 'manual_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }
                $today_funding = Transaction::where('transaction_type', 'manual_funding')->where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
                $today_pending_funding = Transaction::where('transaction_type', 'manual_funding')->where('status', 'pending')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
            }

            if ($funding_type == 'gateway_funding') {  
                if(!empty($status)){
                    $transaction = Transaction::where('status', $status)->where('transaction_type', 'gateway_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }else{
                    $transaction = Transaction::where('transaction_type', 'gateway_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }
                $today_funding = Transaction::where('transaction_type', 'gateway_funding')->where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
                $today_pending_funding = Transaction::where('transaction_type', 'gateway_funding')->where('status', 'pending')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
            }

            if ($funding_type == 'bank_funding') {
                
                if(!empty($status)){
                    $transaction = Transaction::where('status', $status)->where('transaction_type', 'bank_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }else{
                    $transaction = Transaction::where('transaction_type', 'bank_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }
                $today_funding = Transaction::where('transaction_type', 'bank_funding')->where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
                $today_pending_funding = Transaction::where('transaction_type', 'bank_funding')->where('status', 'pending')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
            }

            if ($funding_type == 'transfer_funding') {
                
                if(!empty($status)){
                    $transaction = Transaction::where('status', $status)->where('transaction_type', 'transfer_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }else{
                    $transaction = Transaction::where('transaction_type', 'transfer_funding')->orderBy('created_at', 'DESC')->paginate(30);
                }

                $today_funding = Transaction::where('transaction_type', 'transfer_funding')->where('status', 'success')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
                $today_pending_funding = Transaction::where('transaction_type', 'transfer_funding')->where('status', 'pending')->where('created_at', '>', Carbon::now()->startOfDay())->where('created_at', '<', Carbon::now()->endOfDay())->get()->sum('amount');
            }

            return response()->json(array(
                'message' => 'Funding transaction',
                'data' => [
                    'transaction' => $transaction,
                    'today_funding' => $today_funding,
                    'today_pending_funding' => $today_pending_funding
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function fetchTransactionDetails(Request $request)
    {
        try {

            $reference = $request->input('reference');

            $rule = array(
                'reference' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $transactions = Transaction::with(['fetchSubService', 'fetchPackages'])->where('reference', $reference)->first();

            $user = User::where('id', $transactions->uid)->first();

            return response()->json(array(
                'message' => 'transaction details',
                'data' => [
                    'transaction' => $transactions,
                    'user' => $user,
                    'funding_charges' => FundingCharge::get()
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function manualFunding(Request $request)
    {
        try {

            $reference = $request->input('reference');

            $status = $request->input('status');

            $rule = array(
                'reference' => 'Required',
                'status' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);
            
            //check the tranactions status:
            $transactions = Transaction::where('reference', $reference)->first();
            
            //init response:
            $response = [];
            
            //don't continue if transaction status is already approved:
            if($transaction["status"] == "approved"){
                
                $response = [
                    'message' => "Transaction already approved!"
                ];
                
            } else{

                $funding = new Funding();

                $funding->manualFundingRequest($transactions->uid, $transactions->id, $status, $transactions->amount);

                Transaction::where('reference', $reference)->update([
                    'sid' => $request->user()['sid'],
                ]);
                
                $response = [
                    'message' => "Successfully {$status}d transaction"
                ];
                
            }

            return response()->json($response, 200);

        } catch (\Exception $e) {
            
            $response = [
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ];
            
            return response()->json($response, (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function fundingWithWalletId(Request $request)
    {
        try {

            $wallet_id = $request->input('wallet_id');

            $amount = $request->input('amount');

            $rule = array(
                'wallet_id' => 'Required|exists:wallets',
                'amount' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $funding = new Funding();

            $funding->fundWalletWithWalletId($wallet_id, $amount, $request->user()['sid']);

            return response()->json(array(
                'message' => 'Successfully funded user',
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function debitWithWalletId(Request $request)
    {
        try {

            $wallet_id = $request->input('wallet_id');

            $amount = $request->input('amount');

            $reason = $request->input('reason');

            $rule = array(
                'wallet_id' => 'Required|exists:wallets',
                'amount' => 'Required',
                'reason' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            $funding = new Funding();

            $funding->debitWalletWithWalletId($wallet_id, $amount, $request->user()['sid'], $reason);

            return response()->json(array(
                'message' => 'Successfully debited user',
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function services(Request $request)
    {
        try {

            $service_id = $request->input('service_id');

            $sub_services = [];

            $service = Services::get();

            if (!empty($service)) {

                $sub_services = SubServices::where('service_id', $service_id)->get();

//                $service = Services::where('id', $service_id)->first();
            }

            $packages = [];

            $sub_service_id = $request->input('sub_service_id');

            if (!empty($sub_service_id)) {
                $sub_services = SubServices::where('sid', $sub_service_id)->first();
                $packages = Packages::where('sid', $sub_service_id)->get();
            }

            return response()->json(array(
                'message' => 'services',
                'data' => [
                    'services' => $service,
                    'sub_services' => $sub_services,
                    'packages' => $packages,
                    'external_services' => [
                        'ringo'
                    ]
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function disableService(Request $request)
    {
        try {

            $sid = $request->input('id');

            $rule = array(
                'id' => 'Required|exists:services'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            Services::where('id', $sid)->update([
                'status' => 'in-active'
            ]);

            return response()->json(array(
                'message' => 'Service disable'
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function activateService(Request $request)
    {
        try {

            $sid = $request->input('id');

            $rule = array(
                'id' => 'Required|exists:services'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            Services::where('id', $sid)->update([
                'status' => 'active'
            ]);

            return response()->json(array(
                'message' => 'Service activated'
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function disableSubService(Request $request)
    {
        try {

            $sid = $request->input('sid');

            $rule = array(
                'sid' => 'Required|exists:sub_services'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            SubServices::where('sid', $sid)->update([
                'status' => 'in-active'
            ]);

            // get service id
            $service_id = SubServices::where('sid', $sid)->first()['service_id'];

            return response()->json(array(
                'message' => 'Service Deactivated',
                'data' => [
                    'sub_service' => SubServices::where('service_id', $service_id)->get(),
                    'serviceDetails' => SubServices::where('sid', $service_id)->first(),
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function activateSubService(Request $request)
    {
        try {

            $sid = $request->input('sid');

            $rule = array(
                'sid' => 'Required|exists:sub_services'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            SubServices::where('sid', $sid)->update([
                'status' => 'active'
            ]);

            // get service id
            $service_id = SubServices::where('sid', $sid)->first()['service_id'];

            return response()->json(array(
                'message' => 'Service Activated',
                'data' => [
                    'sub_service' => SubServices::where('service_id', $service_id)->get(),
                    'serviceDetails' => SubServices::where('sid', $service_id)->first(),
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function setMinimumAmount(Request $request)
    {
        try {

            $sid = $request->input('sid');

            $amount = $request->input('min_amount');

            $rule = array(
                'sid' => 'Required|exists:sub_services',
                'min_amount' => 'Required'
            );

            $validator = Validator::make($request->all(), $rule);

            $error = $validator->errors();

            if ($validator->failed())
                throw new \Exception($error->first(), 400);

            SubServices::where('sid', $sid)->update([
                'min_amount' => $amount
            ]);

            // get service id
            $service_id = SubServices::where('sid', $sid)->first()['service_id'];

            return response()->json(array(
                'message' => 'Amount updated',
                'data' => [
                    'sub_service' => SubServices::where('service_id', $service_id)->get(),
                    'serviceDetails' => SubServices::where('sid', $service_id)->first(),
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function fetchExternalService(Request $request)
    {
        try {

            $service_id = $request->input('service_id');

            $service = $request->input('service_type');

            $phone_number = $request->input('phone_number');

            $type = $request->input('type');

            $other_service_type = $request->input('other_service_type');

            $service_data = ServiceRender::fetchPackages($service, $phone_number, $type, $other_service_type);
            
            return response()->json(array(
                'message' => 'services',
                'data' => [
                    'type' => $type,
                    'service' => $service_data,
                    'packages' => Packages::where('sid', $service_id)->where('medium', 'LIKE', "%$service%")->get(),
                    'service_types' => [
                        'MTN', 'Airtel', '9Mobile', 'Glo'
                    ]
                ]
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }

    public function savePackagesData(Request $request)
    {
        try {

            $service = $request->input('service_type');

            $type = $request->input('type');

            $data = $request->input('data');
            
            $other_service = $request->input('other_service');
            
            ServiceRender::saveServicePackages($type, $service, $data, $other_service);

            return response()->json(array(
                'message' => 'Packages Saved',
            ), 200);

        } catch (\Exception $e) {
            return response()->json(array(
                'code' => 0,
                'message' => 'An error occurred',
                'short_description' => $e->getMessage().$e->getLine().$e->getFile()
            ), (in_array($e->getCode(), General::httpErrorCode()) ? $e->getCode() : 500));
        }
    }
    
    public function editDataVendor(Request $request){
        
        $newVendorChoice = $request->input('data_vendor_choice');
        try{
            //this will be 
            Admin::first()->update([
                'adminVendorChoice' => $newVendorChoice
            ]);
            
            return $response()->json([
                'status' => 'vendor_changed',
                'message' => 'Data Vendor changed successfully. '
            ], 200);  
        }catch(\Exception $ex){
            
            return $response()->json([
                'status' => 'vendor_not_changed',
                'message' => 'Data Vendor unsuccessfully modified!'
            ], 500);  
        }
        
    }

    public function fetchMembershipPlan(Request $request)
    {
        return response()->json(
            array(
                'data' => [
                    'membership_level' => MembershipPlan::get()
                ]
            ),
            200
        );
    }

    public function updateMemberShipPlan(Request $request)
        {
    
            MembershipPlan::where('id', $request->input('id'))->update([
                'upgrade_amount' => $request->input('upgrade_amount'),
                'discount_amount' => $request->input('discount_amount'),
                'ns_coin_discount_amount' => $request->input('ns_coin_discount_amount'),
                'airtime_discount' => $request->input('airtime_discount'),
                'data_discount' => $request->input('data_discount'),
                'cable_discount' => $request->input('cable_discount'),
                'electricity_discount' => $request->input('electricity_discount'),
                'direct_down_line_data_commission' => $request->input('direct_down_line_data_commission'),
                'direct_down_line_referral_commission' => $request->input('direct_down_line_referral_commission'),
                'in_direct_down_line_referral_commission' => $request->input('in_direct_down_line_referral_commission'),
                'other_generation_commission' => $request->input('other_generation_commission'),
                'last_generation' => $request->input('last_generation'),
                'subscription_bonus' => $request->input('subscription_bonus')
            ]);
    
            return response()->json(
                array(
                    'message' => 'Plan updated successfully'
                ),
                200
            );
    
        }

    public function manualBanking(Request $request)
    {
        return response()->json(
            array(
                'data' => [
                    'banks' => AdminBanks::get()
                ]
            ),
            200
        );
    }

    public function createBanking(Request $request)
    {
        AdminBanks::create([
            'bank_name' => $request->input('bank_name'),
            'account_name' => $request->input('account_name'),
            'account_number' => $request->input('account_number')
        ]);

        return response()->json(
            array(
                'message' => 'Successfully created accounts'
            ),
            200
        );
    }

    public function editBanks(Request $request)
    {
        AdminBanks::where('id', $request->input('id'))->update([
            'bank_name' => $request->input('bank_name'),
            'account_name' => $request->input('account_name'),
            'account_number' => $request->input('account_number')
        ]);

        return response()->json(
            array(
                'message' => 'Successfully updated accounts'
            ),
            200
        );
    }

    public function deleteBank(Request $request)
    {
        AdminBanks::where('id', $request->input('id'))->delete();

        return response()->json(
            array(
                'message' => 'Successfully updated accounts'
            ),
            200
        );
    }

    public function fetchStaff(Request $request)
    {
        return response()->json(
            array(
                'data' => [
                    'staff' => Staff::get()
                ]
            ),
            200
        );
    }

    public function createStaff(Request $request)
    {
        $status = array();

        try {

            // register new users

            $name = $request->input('name');

            $email = $request->input('email');

            $phone = $request->input('phone');

            $password = $request->input('password');

            $rules = array(
                'name' => 'Required',
                'email' => 'Required|unique:staff',
                'phone' => 'Required|unique:staff',
                'password' => 'Required'
            );

            $validation = Validator::make($request->all(), $rules);

            $errors = $validation->errors();

            if ($validation->failed())
                throw new  \Exception($errors->first(), 400);

            $staff = new Staff();

            $staff->name = $name;

            $staff->email = $email;

            $staff->phone = $phone;

            $staff->password = password_hash($password, PASSWORD_DEFAULT);

            $staff->save();

            $status['code'] = 1;

            $status['message'] = 'success';

            $status['short_description'] = 'Successfully created account';

        } catch (\Exception $e) {

            $status['code'] = 0;

            $status['message'] = 'An error occurred';

            $status['short_description'] = $e->getMessage();

            return response()->json($status, 400);
        }

        return response()->json($status, 200);

    }

    public function editStaff(Request $request)
    {

        Staff::where('sid', $request->input('sid'))->update([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone')
        ]);

        return response()->json(
            array(
                'message' => 'Successfully updated account'
            ),
            200
        );
    }

    public function updatePassword(Request $request)
    {

        Staff::where('sid', $request->input('sid'))->update([
            'password' => password_hash($request->input('password'), PASSWORD_DEFAULT),
        ]);

        return response()->json(
            array(
                'message' => 'Successfully updated account'
            ),
            200
        );
    }

    public function deleteStaff(Request $request)
    {

        Staff::where('sid', $request->input('sid'))->delete();

        return response()->json(
            array(
                'message' => 'Successfully deleted account'
            ),
            200
        );
    }
    
    //about our epin: will it be that the system will auto-generate it 
    //or would it be the admin CSV file uploaded? 
    public function createEPin(Request $request)
    {

        $file = $request->file('csv_file');

        if (!empty($file)) {
            $csv = fopen($file, 'r');


            fgetcsv($csv);

            while (($line = fgetcsv($csv)) !== false) {

                $network_provider = $line[0];
                $amount = $line[1];
                $pin = $line[2];
                $serial = $line[3];
                $type = $line[4];

                Epin::create([
                    'tag' => Carbon::now() . '-' . Str::random(10),
                    'network_provider' => $network_provider,
                    'amount' => $amount,
                    'pin' => $pin,
                    'serial' => $serial,
                    'type' => $type
                ]);
            };

            return response()->json(
                array(
                    'message' => 'Successfully created epin'
                ),
                200
            );
        }

        return response()->json(
            array(
                'message' => 'Invalid file provided'
            ),
            400
        );

    }

    public function updateEPin(Request $request)
    {
        Epin::where('epin', $request->input('epin'))->update([
            'tag' => Carbon::now() . '-' . Str::random(10),
            'network_provider' => $request->input('network_provider'),
            'amount' => $request->input('amount'),
            'pin' => $request->input('pin'),
            'serial' => $request->input('serial'),
            'type' => $request->input('type')
        ]);

        return response()->json(
            array('message' => 'Successfully updated e-pin'),
            200
        );
    }

    public function deleteEPin(Request $request)
    {
        Epin::where('epin', $request->input('epin'))->delete();

        return response()->json(
            array('message' => 'Successfully deleted e-pin'),
            200
        );
    }

    public function fetchEPin(Request $request)
    {

        $type = $request->input('type');

        $data = [];

        if (!empty($type)) {
            $data = Epin::where('type', $type)->orderBy('created_at', 'DESC')->paginate(30);
        } else {
            $data = Epin::orderBy('created_at', 'DESC')->paginate(30);
        }

        return response()->json(
            array(
                'data' => [
                    'e_pin' => $data,
                    'd' => $type
                ]
            ),
            200
        );
    }
    
        public function fetchMlmUser(Request $request)
    {
        $mlm_level = $request->input('level');

        $users = [];

        if (!empty($mlm_level)) {

            $users = User::withCount('referredUser')->where('membership_level')->paginate(30);

        } else {
            $users = User::withCount('referredUser')->paginate(30);
        }

        return response()->json([
            'data' => [
                'user' => $users
            ]
        ], 200);

    }

    public function fetchDownLine(Request $request)
    {

        $ref_id = $request->input('ref_id');


        $user = User::where('referral', $ref_id)->paginate(30);

        return response()->json([
            'data' => [
                'user' => $user
            ]
        ], 200);

    }

     public function swapUpLine(Request $request)
    {

        $ref_uid = $request->input('ref_uid');

        $upline_email = $request->input('email');

        if (empty($upline_email)) {
            return response()->json(['message' => 'New upline email is required'], 400);
        }

        $upline_details = User::where('email', $upline_email)->first();

        $upline_uid = $upline_details['id'];

        if ($upline_details) {

            $ref = User::find($ref_uid);
            $ref->referral = $upline_details['referral_id'];
            $ref->save();

            return response()->json(['message' => 'Uplone swaped successful'], 200);
        }

        return response()->json(['message' => 'Invalid upline provided'], 400);
    }
       public function settings(Request $request)
    {
        return response()->json([
            'data' => [
                'settings' => Setting::get()
            ]
        ], 200);
    }
     public function updateDollarSellingRate(Request $request)
    {

        $id = $request->input('id');

        Setting::where('id', $id)->update([
            'keys' => $request->input('amount')
        ]);

        return response()->json(['message' => 'Updated selling rate amount'], 200);
    }

    public function updateDollarBuyingRate(Request $request)
    {

        $id = $request->input('id');

        Setting::where('id', $id)->update([
            'keys' => $request->input('amount')
        ]);

        return response()->json(['message' => 'Updated buying rate amount'], 200);
    }
    
        public function adminNotification(Request $request)
    {
        return response()->json(
            array(
                'message' => 'Notification',
                'data' => [
                    'all_notification' => Notification::where('uid', 'admin')->orderBy('created_at', 'DESC')->paginate(25)
                ]
            ),
            200
        );
    }

    public function createNotification(Request $request)
    {

        $message = $request->input('message');

        Notification::create([
            'uid' => 'admin',
            'message' => $message,
            'medium' => 'in-app',
            'status' => 'un-read'
        ]);

        return response()->json(array(
            'message' => 'Notification created successfully'
        ), 200);

    }

    public function updateNotificationStatus(Request $request)
    {

        $status = $request->input('status');

        $id = $request->input('id');

        Notification::where('id', $id)->update([
            'status' => $status
        ]);

        return response()->json(
            array(
                'message' => 'Action successful'
            ),
            200
        );
    }

    public function deleteNotification(Request $request)
    {

        $id = $request->input('id');

        Notification::where('id', $id)->delete();

        return response()->json(
            array(
                'message' => 'Action successful'
            ),
            200
        );
    }

    public function updateNotification(Request $request)
    {

        $message = $request->input('message');

        $id = $request->input('id');

        Notification::where('id', $id)->update([
            'message' => $message
        ]);

        return response()->json(
            array(
                'message' => 'Action successful'
            ),
            200
        );
    }
    
    public function updateMembership(Request $request)
    {

        $upgrade_amount = $request->input('upgrade_amount');
        $discount_amount = $request->input('discount_amount');
        $ns_coin_amount = $request->input('ns_coin_amount');
            $airtime_amount = $request->input('airtime_amount');
        $data_discount = $request->input('data_discount');
        $cable_discount = $request->input('cable_discount');
        $electricity_discount = $request->input('electricity_discount');

        $membership = new MembershipPlan();
        $membership->upgrade_amount = $upgrade_amount;
        $membership->discount_amount = $discount_amount;
        $membership->ns_coin_discount_amount = $ns_coin_amount;
        $membership->airtime_discount = $airtime_amount;
        $membership->data_discount = $data_discount;
        $membership->cable_discount = $cable_discount;
        $membership->electricity_discount = $electricity_discount;
        $membership->save();

        return response()->json(['message' => 'Successfully update membership plan'], 200);

    }
}
