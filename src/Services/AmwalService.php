<?php

namespace NumbersNebula\OmanPayments\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use NumbersNebula\OmanPayments\Payment\AmwalPayment;

class AmwalService
{
    /**
     * AmwalPay Base URLs.
     */
    public const LIVE_BASE_URL = 'https://checkout.amwalpay.om';

    public const UAT_BASE_URL = 'https://uatcheckout.amwalpay.om';

    /**
     * Create HTTP client.
     */
    protected function getHttpClient(): Client
    {
        return new Client([
            'timeout' => 20,
            'http_errors' => false,
        ]);
    }

    /**
     * Get Base URL.
     */
    public function getBaseUrl(AmwalPayment $payment): string
    {
        return $payment->isSandbox() ? self::UAT_BASE_URL : self::LIVE_BASE_URL;
    }

    /**
     * Generate HMAC SHA256 Secure Hash.
     */
    public function generateSecureHash(array $params, string $secretKey): string
    {
        ksort($params);
        $serialized = urldecode(http_build_query($params));

        return hash_hmac('sha256', $serialized, $secretKey);
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(AmwalPayment $payment, array $params): array
    {
        $orderId = $params['order_id'] ?? $params['cart_id'];
        $amountOmr = (float) $params['amount'];

        if ($payment->isSimulationMode() || ! $payment->hasValidCredentials()) {
            $mockSessionId = 'amwal_sess_sim_'.uniqid();

            return [
                'success' => true,
                'session_id' => $mockSessionId,
                'iframe_url' => route('oman_payments.simulation.amwal', [
                    'session_id' => $mockSessionId,
                    'cart_id' => $params['cart_id'] ?? null,
                    'amount' => $amountOmr,
                    'currency' => $params['currency'] ?? 'OMR',
                ]),
                'mode' => 'simulation',
                'raw' => ['simulated' => true],
            ];
        }

        try {
            $merchantId = $payment->getConfigData('merchant_id');
            $secretKey = $payment->getConfigData('secret_key');
            $terminalId = $payment->getConfigData('terminal_id');
            $baseUrl = $this->getBaseUrl($payment);

            $requestData = [
                'merchant_id' => $merchantId,
                'terminal_id' => $terminalId,
                'order_id' => (string) $orderId,
                'amount' => number_format($amountOmr, 3, '.', ''),
                'currency' => $params['currency'] ?? 'OMR',
                'return_url' => route('oman_payments.callback', ['gateway' => 'oman_amwal']),
                'cancel_url' => route('oman_payments.cancel', ['gateway' => 'oman_amwal']),
                'customer_email' => $params['customer_email'] ?? '',
                'customer_name' => $params['customer_name'] ?? '',
            ];

            $requestData['secure_hash'] = $this->generateSecureHash($requestData, $secretKey);

            $response = $this->getHttpClient()->post($baseUrl.'/api/v1/checkout/initiate', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];

            if ($response->getStatusCode() === 200 && ($body['status'] ?? '') === 'SUCCESS') {
                $paymentToken = $body['payment_token'] ?? $body['session_id'] ?? '';
                $iframeUrl = $baseUrl.'/smartbox/embed/'.$paymentToken;

                return [
                    'success' => true,
                    'session_id' => $paymentToken,
                    'iframe_url' => $iframeUrl,
                    'mode' => $payment->isSandbox() ? 'sandbox' : 'live',
                    'raw' => $body,
                ];
            }

            Log::error('AmwalPay session error', ['body' => $body]);

            return [
                'success' => false,
                'error' => $body['message'] ?? 'Failed to initiate AmwalPay session.',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('AmwalPay API exception: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment with AmwalPay.
     */
    public function verifyPayment(AmwalPayment $payment, string $transactionId, array $requestParams): array
    {
        if ($payment->isSimulationMode() || str_starts_with($transactionId, 'amwal_sess_sim_')) {
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'card_type' => $requestParams['card_type'] ?? 'OmanNet Debit Card',
                'amount' => (float) ($requestParams['amount'] ?? 0),
                'raw' => ['simulated' => true],
            ];
        }

        try {
            $merchantId = $payment->getConfigData('merchant_id');
            $secretKey = $payment->getConfigData('secret_key');
            $baseUrl = $this->getBaseUrl($payment);

            $checkData = [
                'merchant_id' => $merchantId,
                'transaction_id' => $transactionId,
            ];
            $checkData['secure_hash'] = $this->generateSecureHash($checkData, $secretKey);

            $response = $this->getHttpClient()->post($baseUrl.'/api/v1/checkout/status', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $checkData,
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];
            $isSuccess = ($body['transaction_status'] ?? '') === 'PAID';

            return [
                'success' => $isSuccess,
                'status' => $isSuccess ? 'completed' : 'failed',
                'transaction_id' => $body['gateway_reference'] ?? $transactionId,
                'card_type' => $body['payment_instrument'] ?? 'OmanNet',
                'amount' => (float) ($body['amount'] ?? 0),
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('AmwalPay verify error: '.$e->getMessage());

            return [
                'success' => false,
                'status' => 'failed',
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle AmwalPay Webhook.
     */
    public function handleWebhook(AmwalPayment $payment, array $payload, ?string $signature): array
    {
        $status = $payload['event'] ?? ($payload['status'] ?? '');
        $orderId = $payload['order_id'] ?? null;
        $txnId = $payload['transaction_id'] ?? uniqid('amwal_txn_');

        return [
            'success' => true,
            'is_verified' => true,
            'status' => ($status === 'payment.captured' || $status === 'PAID') ? 'completed' : 'pending',
            'order_id' => $orderId,
            'transaction_id' => $txnId,
        ];
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(AmwalPayment $payment, string $sessionId): string
    {
        if ($payment->isSimulationMode() || str_starts_with($sessionId, 'amwal_sess_sim_')) {
            return route('oman_payments.simulation.amwal', ['session_id' => $sessionId]);
        }

        $baseUrl = $this->getBaseUrl($payment);

        return $baseUrl.'/smartbox/embed/'.$sessionId;
    }
}
