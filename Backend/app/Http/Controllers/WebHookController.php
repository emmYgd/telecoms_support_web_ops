<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Service\Funding;
use App\Http\Controllers\Service\General;
use App\Http\Controllers\Service\CoinPayment;

use App\User;

use Illuminate\Http\Request;

class WebHookController extends Controller
{
    private $client_secret;

    private $funding;

    public function __construct()
    {
        $this->funding = new Funding();

        // client secret for providus initiate on call
        $this->client_secret = $this->funding->secret_key;

    }

    public function calculateHashPayment($payment_reference, $amount_paid, $paid_on, $transaction_reference)
    {
        return hash('SHA512', $this->client_secret . '|' . $payment_reference . '|' . $amount_paid . '|' . $paid_on . '|' . $transaction_reference);
    }

    public function providusAccountFunding(Request $request)
    {
        try {

            $responseBody = json_decode($request->getContent(), true);

            // calculate_has
            $calculated_hash = $this->calculateHashPayment($responseBody['paymentReference'], $responseBody['amountPaid'], $responseBody['paidOn'], $responseBody['transactionReference']);

            if ($calculated_hash !== $responseBody['transactionHash']) {

                General::logActivities('system_payment_webhook', json_encode($responseBody));

                throw new \Exception('Hash token does not match.');
            }

            $user = User::where('email', $responseBody['customer']['email'])->first();

            $this->funding->providusApiFunding($user['id'], $responseBody['amountPaid'], $responseBody);

            General::logActivities('system', 'Providus bank transfer for ' . $user['email'] . ' has been credited');

        } catch (\Exception $e) {
            General::logActivities('system', json_encode(['message' => $e->getMessage(), 'code' => $e->getCode(), 'line' => $e->getLine()]));
        }
    }

    public static function liveBTCConversion(Request $request)
    {

        try {

            return CoinPayment::liveBTCRate($request->input('amount'));

        } catch (\Exception $e) {

//                var_dump($e->getMessage());
            return 0;

        }

    }
}
