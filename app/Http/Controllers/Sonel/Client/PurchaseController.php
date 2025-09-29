<?php

namespace App\Http\Controllers\Sonel\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Purchase;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\QuestResponse;
use Hacp0012\Quest\SpawMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class PurchaseController extends Controller
{
    #[QuestSpaw(ref: 'l6ejN7G1R')]
    function purchase(
        string $phoneCode,
        string $phoneNumber,
        float $amount,
        string $currency,
        string $provider,
        string $cNumber,
        string $buyerPhoneCode,
        string $buyerPhoneNumber,
    ): bool {
        $res = new QuestResponse(ref: 'l6ejN7G1R', dataName: 'success');

        $buyer = Client::firstWhere('phone', "$buyerPhoneCode,$buyerPhoneNumber");
        if ($buyer) {
            $created = Purchase::create([
                'state'     => 'INWAIT_PAIEMENT',
                'amount'    => $amount,
                'currency'  => $currency,
                'provider'  => $provider,
                'phone'     => "$phoneCode,$phoneNumber",
                'c_number'  => $cNumber,
                'buyer'     => "$buyerPhoneCode,$buyerPhoneNumber",
                'expire_at' => now()->addHours(24), // Expire after 24H 
            ]);

            if ($created) {
                $paiementState = $this->requestPurchase(
                    purchaseId: $created->id,
                    phone: "$phoneCode$phoneNumber",
                    provider: $provider,
                    amount: $amount,
                    currency: $currency,
                );

                if ($paiementState['status'] == false) {
                    $res->setData('paiement_state', 'FAILED');
                    if (isset($paiementState['message']) && $paiementState['message'] != null) $res->message($paiementState['message']);
                } else $res->setData('paiement_state', 'SUCCESS');

                return true;
            }
        }

        return false;
    }

    private function requestPurchase(
        int $purchaseId,
        string $phone,
        string $provider,
        float $amount,
        string $currency,
    ): array {
        // return true;
        $purchase = Purchase::find($purchaseId);
        if ($purchase) {
            $client = Client::firstWhere('phone', $purchase->buyer);
            if ($client) {
                $phoneProvider = match ($provider) {
                    'AIRTEL'    => 'AIRTEL',
                    'ORANGE'    => 'ORANGE',
                    'VODACOM'   => 'MPESA',
                    'AFRICEL'   => 'AFRICEL',
                    default     => 'AIRTEL',
                };
                $transactonRef = Uuid::uuid4();
                $response = Http::post('https://marchand.maishapay.online/api/collect/v2/store/mobileMoney', [
                    "transactionReference" => $transactonRef, // required|unique
                    "gatewayMode" => env('MAISHAPAY_IS_PRODUCTION', false) ? "1" : "0",  // Gateway mode 1: Production; 0: Sandbox
                    "publicApiKey" => env('MAISHAPAY_IS_PRODUCTION', false) ? env('MAISHAPAY_PUBLIC_KEY', '') : env('MAISHAPAY_SANDBOX_PUBLIC_KEY'), // your public API key, required|string
                    "secretApiKey" => env('MAISHAPAY_IS_PRODUCTION', false) ? env('MAISHAPAY_PRIVATE_KEY', '') : env('MAISHAPAY_SANDBOX_PRIVATE_KEY'), // your secret API key, required|string
                    "order" => [
                        "amount" => "$amount",
                        "currency" => $currency, // currency CDF, USD, XAF, XOF, ....
                        "customerFullName" => $client->name ?: uniqid(prefix: 'customer_'),
                        "customerEmailAdress" => "princeieugene48@gmail.com"
                    ],
                    "paymentChannel" => [
                        "channel" => "MOBILEMONEY",
                        "provider" => $phoneProvider,  // required : AIRTEL, ORANGE, MTN, ....
                        "walletID" => "+" . $phone,
                        "callbackUrl" => "https://cashpower.collecta.top/api/client/purchase/RVlioIxTX",
                    ]
                ]);

                // Log::debug($response);
                $purchase->response = $response->json();
                $purchase->transaction_ref = $transactonRef;
                $purchase->save();
                if ($response['status_code'] == 200 && $response['transactionStatus'] == 'SUCCESS') {
                    return ['status' => true];
                } elseif ($response['status_code'] == 400) {
                    if (isset($response['type']) && $response['type'] == 'errors') return ['status' => false, 'message' => $response['errors']['message'] ?: null];
                    return ['status' => false, 'message' => null];
                }
            }
        }

        return ['status' => false, 'message' => null];
    }

    #[QuestResponse(ref: "RVlioIxTX", method: SpawMethod::GET)]
    function paiementRequestCallback(Request $request)
    {
        Log::alert($request->input());
    }
}
