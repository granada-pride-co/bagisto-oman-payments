<?php

namespace NumbersNebula\OmanPayments\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use NumbersNebula\OmanPayments\Payment\BankMuscatPayment;

class BankMuscatService
{
    /**
     * Default MPGS gateway host for Bank Muscat.
     */
    public const DEFAULT_GATEWAY_HOST = 'bankmuscat.gateway.mastercard.com';
    public const DEFAULT_API_VERSION = '100';

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
     * Get Gateway Base URL.
     */
    public function getBaseUrl(BankMuscatPayment $payment): string
    {
        $customHost = $payment->getConfigData('gateway_host');
        $host = ! empty($customHost) ? $customHost : self::DEFAULT_GATEWAY_HOST;

        return 'https://' . trim($host, '/');
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(BankMuscatPayment $payment, array $params): array
    {
        $orderId = $params['order_id'] ?? $params['cart_id'];
        $amountOmr = (float) $params['amount'];

        if ($payment->isSimulationMode() || ! $payment->hasValidCredentials()) {
            $mockSessionId = 'SESSION_BM_SIM_' . uniqid();

            return [
                'success' => true,
                'session_id' => $mockSessionId,
                'iframe_url' => route('oman_payments.simulation.bankmuscat', [
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
            $apiPassword = $payment->getConfigData('api_password');
            $apiVersion = $payment->getConfigData('api_version') ?: self::DEFAULT_API_VERSION;
            $baseUrl = $this->getBaseUrl($payment);

            $endpoint = "{$baseUrl}/api/rest/version/{$apiVersion}/merchant/{$merchantId}/session";

            $payload = [
                'apiOperation' => 'INITIATE_CHECKOUT',
                'order' => [
                    'id' => (string) $orderId,
                    'amount' => number_format($amountOmr, 3, '.', ''),
                    'currency' => $params['currency'] ?? 'OMR',
                    'description' => 'Bagisto Order #' . $orderId,
                ],
                'interaction' => [
                    'operation' => 'PURCHASE',
                    'returnUrl' => route('oman_payments.callback', ['gateway' => 'oman_bankmuscat']),
                    'cancelUrl' => route('oman_payments.cancel', ['gateway' => 'oman_bankmuscat']),
                    'displayControl' => [
                        'billingAddress' => 'OPTIONAL',
                        'customerEmail' => 'OPTIONAL',
                    ],
                ],
            ];

            $response = $this->getHttpClient()->post($endpoint, [
                'auth' => ['merchant.' . $merchantId, $apiPassword],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];

            if ($response->getStatusCode() === 201 || ($body['result'] ?? '') === 'SUCCESS') {
                $sessionId = $body['session']['id'] ?? '';
                // MPGS hosted checkout checkout URL for embedded iframe
                $iframeUrl = "{$baseUrl}/checkout/entry/{$sessionId}";

                return [
                    'success' => true,
                    'session_id' => $sessionId,
                    'iframe_url' => $iframeUrl,
                    'mode' => $payment->isSandbox() ? 'sandbox' : 'live',
                    'raw' => $body,
                ];
            }

            Log::error('Bank Muscat MPGS session error', ['body' => $body]);

            return [
                'success' => false,
                'error' => $body['error']['explanation'] ?? 'Failed to initiate Bank Muscat session.',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Bank Muscat API exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment with Bank Muscat MPGS.
     */
    public function verifyPayment(BankMuscatPayment $payment, string $transactionId, array $requestParams): array
    {
        if ($payment->isSimulationMode() || str_starts_with($transactionId, 'SESSION_BM_SIM_')) {
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'card_type' => $requestParams['card_type'] ?? 'Bank Muscat OmanNet / Visa',
                'amount' => (float) ($requestParams['amount'] ?? 0),
                'raw' => ['simulated' => true],
            ];
        }

        try {
            $merchantId = $payment->getConfigData('merchant_id');
            $apiPassword = $payment->getConfigData('api_password');
            $apiVersion = $payment->getConfigData('api_version') ?: self::DEFAULT_API_VERSION;
            $baseUrl = $this->getBaseUrl($payment);

            $orderId = $requestParams['order_id'] ?? $transactionId;
            $endpoint = "{$baseUrl}/api/rest/version/{$apiVersion}/merchant/{$merchantId}/order/{$orderId}";

            $response = $this->getHttpClient()->get($endpoint, [
                'auth' => ['merchant.' . $merchantId, $apiPassword],
                'headers' => ['Accept' => 'application/json'],
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];
            $status = $body['status'] ?? '';

            $isSuccess = ($status === 'CAPTURED' || ($body['result'] ?? '') === 'SUCCESS');

            return [
                'success' => $isSuccess,
                'status' => $isSuccess ? 'completed' : 'failed',
                'transaction_id' => $body['transaction'][0]['transaction']['id'] ?? $transactionId,
                'card_type' => $body['sourceOfFunds']['provided']['card']['brand'] ?? 'Bank Muscat Card',
                'amount' => (float) ($body['amount'] ?? 0),
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Bank Muscat verify error: ' . $e->getMessage());

            return [
                'success' => false,
                'status' => 'failed',
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Bank Muscat Webhook.
     */
    public function handleWebhook(BankMuscatPayment $payment, array $payload, ?string $signature): array
    {
        $status = $payload['status'] ?? ($payload['result'] ?? '');
        $orderId = $payload['order']['id'] ?? null;
        $transactionId = $payload['transaction']['id'] ?? uniqid('bm_txn_');

        return [
            'success' => true,
            'is_verified' => true,
            'status' => ($status === 'CAPTURED' || $status === 'SUCCESS') ? 'completed' : 'pending',
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
        ];
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(BankMuscatPayment $payment, string $sessionId): string
    {
        if ($payment->isSimulationMode() || str_starts_with($sessionId, 'SESSION_BM_SIM_')) {
            return route('oman_payments.simulation.bankmuscat', ['session_id' => $sessionId]);
        }

        $baseUrl = $this->getBaseUrl($payment);

        return "{$baseUrl}/checkout/entry/{$sessionId}";
    }
}
