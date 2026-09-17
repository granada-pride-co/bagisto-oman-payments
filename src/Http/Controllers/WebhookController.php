<?php

namespace NumbersNebula\OmanPayments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use NumbersNebula\OmanPayments\Models\OmanPaymentTransaction;
use NumbersNebula\OmanPayments\Models\OmanPaymentWebhook;
use NumbersNebula\OmanPayments\Payment\AmwalPayment;
use NumbersNebula\OmanPayments\Payment\BankMuscatPayment;
use NumbersNebula\OmanPayments\Payment\PaymobPayment;
use NumbersNebula\OmanPayments\Payment\ThawaniPayment;

class WebhookController extends Controller
{
    /**
     * Map of gateways.
     */
    protected array $gatewayMap = [
        'oman_thawani' => ThawaniPayment::class,
        'oman_bankmuscat' => BankMuscatPayment::class,
        'oman_amwal' => AmwalPayment::class,
        'oman_paymob' => PaymobPayment::class,
    ];

    /**
     * Handle incoming gateway webhook notification.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('x-signature')
            ?? $request->header('thawani-signature')
            ?? $request->input('hmac')
            ?? $request->header('x-amwal-hash');

        $logEntry = OmanPaymentWebhook::create([
            'gateway' => $gateway,
            'event_type' => $payload['event_type'] ?? ($payload['event'] ?? ($payload['type'] ?? 'payment_notification')),
            'payload' => $payload,
            'signature' => $signature,
            'is_verified' => false,
            'processed' => false,
        ]);

        if (! isset($this->gatewayMap[$gateway])) {
            $logEntry->update(['error_log' => 'Unknown gateway: '.$gateway]);

            return response()->json(['status' => 'error', 'message' => 'Unknown gateway'], 400);
        }

        try {
            $gatewayInstance = app($this->gatewayMap[$gateway]);
            $result = $gatewayInstance->handleWebhook($payload, $signature);

            $logEntry->update([
                'is_verified' => $result['is_verified'] ?? true,
                'processed' => true,
            ]);

            if (($result['status'] ?? '') === 'completed' && ! empty($result['transaction_id'])) {
                OmanPaymentTransaction::where('transaction_id', $result['transaction_id'])
                    ->orWhere('session_id', $result['transaction_id'])
                    ->update(['status' => 'completed']);
            }

            return response()->json(['status' => 'success', 'data' => $result]);
        } catch (\Throwable $e) {
            Log::error("Webhook error for {$gateway}: ".$e->getMessage());
            $logEntry->update(['error_log' => $e->getMessage()]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
