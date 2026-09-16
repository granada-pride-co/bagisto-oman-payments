<?php

namespace NumbersNebula\OmanPayments\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use NumbersNebula\OmanPayments\Payment\PaymobPayment;

class PaymobService
{
    /**
     * Paymob Base URL.
     */
    public const BASE_URL = 'https://accept.paymob.com';

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
     * Initiate payment session and get iFrame URL.
     */
    public function initiateSession(PaymobPayment $payment, array $params): array
    {
        $orderId = $params['order_id'] ?? $params['cart_id'];
        $amountOmr = (float) $params['amount'];
        $amountCents = (int) round($amountOmr * 1000); // 1 OMR = 1000 Baisa

        if ($payment->isSimulationMode() || ! $payment->hasValidCredentials()) {
            $mockSessionId = 'paymob_sim_token_' . uniqid();

            return [
                'success' => true,
                'session_id' => $mockSessionId,
                'iframe_url' => route('oman_payments.simulation.paymob', [
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
            $apiKey = $payment->getConfigData('api_key');
            $integrationId = $payment->getConfigData('integration_id');
            $iframeId = $payment->getConfigData('iframe_id');

            // Step 1: Authentication
            $authResponse = $this->getHttpClient()->post(self::BASE_URL . '/api/auth/tokens', [
                'json' => ['api_key' => $apiKey],
            ]);
            $authData = json_decode((string) $authResponse->getBody(), true) ?? [];
            $authToken = $authData['token'] ?? null;

            if (! $authToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate with Paymob.',
                    'raw' => $authData,
                ];
            }

            // Step 2: Order Registration
            $orderResponse = $this->getHttpClient()->post(self::BASE_URL . '/api/ecommerce/orders', [
                'json' => [
                    'auth_token' => $authToken,
                    'delivery_needed' => 'false',
                    'amount_cents' => (string) $amountCents,
                    'currency' => $params['currency'] ?? 'OMR',
                    'merchant_order_id' => (string) $orderId,
                    'items' => [],
                ],
            ]);
            $orderData = json_decode((string) $orderResponse->getBody(), true) ?? [];
            $paymobOrderId = $orderData['id'] ?? null;

            if (! $paymobOrderId) {
                return [
                    'success' => false,
                    'error' => 'Failed to register order with Paymob.',
                    'raw' => $orderData,
                ];
            }

            // Step 3: Payment Key Generation
            $nameParts = explode(' ', trim($params['customer_name'] ?? 'Guest Customer'), 2);
            $keyResponse = $this->getHttpClient()->post(self::BASE_URL . '/api/acceptance/payment_keys', [
                'json' => [
                    'auth_token' => $authToken,
                    'amount_cents' => (string) $amountCents,
                    'expiration' => 3600,
                    'order_id' => (string) $paymobOrderId,
                    'billing_data' => [
                        'first_name' => $nameParts[0] ?: 'Customer',
                        'last_name' => $nameParts[1] ?? 'Oman',
                        'email' => $params['customer_email'] ?? 'customer@example.com',
                        'phone_number' => '+96890000000',
                        'apartment' => 'NA',
                        'floor' => 'NA',
                        'street' => 'Sultan Qaboos St',
                        'building' => 'NA',
                        'postal_code' => '100',
                        'city' => 'Muscat',
                        'country' => 'OMN',
                        'state' => 'Muscat',
                    ],
                    'currency' => $params['currency'] ?? 'OMR',
                    'integration_id' => $integrationId,
                ],
            ]);

            $keyData = json_decode((string) $keyResponse->getBody(), true) ?? [];
            $paymentToken = $keyData['token'] ?? null;

            if (! $paymentToken) {
                return [
                    'success' => false,
                    'error' => 'Failed to generate payment key from Paymob.',
                    'raw' => $keyData,
                ];
            }

            $iframeUrl = self::BASE_URL . "/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}";

            return [
                'success' => true,
                'session_id' => $paymentToken,
                'iframe_url' => $iframeUrl,
                'mode' => $payment->isSandbox() ? 'sandbox' : 'live',
                'raw' => ['paymob_order_id' => $paymobOrderId],
            ];
        } catch (\Throwable $e) {
            Log::error('Paymob API exception: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment with Paymob.
     */
    public function verifyPayment(PaymobPayment $payment, string $transactionId, array $requestParams): array
    {
        if ($payment->isSimulationMode() || str_starts_with($transactionId, 'paymob_sim_token_')) {
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'card_type' => $requestParams['card_type'] ?? 'Paymob OmanNet / Card',
                'amount' => (float) ($requestParams['amount'] ?? 0),
                'raw' => ['simulated' => true],
            ];
        }

        $success = filter_var($requestParams['success'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isPending = filter_var($requestParams['pending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $txnId = $requestParams['id'] ?? $transactionId;

        $isSuccess = $success && ! $isPending;

        return [
            'success' => $isSuccess,
            'status' => $isSuccess ? 'completed' : 'failed',
            'transaction_id' => (string) $txnId,
            'card_type' => $requestParams['source_data_type'] ?? 'Card',
            'amount' => isset($requestParams['amount_cents']) ? ($requestParams['amount_cents'] / 1000) : 0,
            'raw' => $requestParams,
        ];
    }

    /**
     * Handle Paymob Webhook.
     */
    public function handleWebhook(PaymobPayment $payment, array $payload, ?string $signature): array
    {
        $obj = $payload['obj'] ?? $payload;
        $success = (bool) ($obj['success'] ?? false);
        $orderId = $obj['order']['merchant_order_id'] ?? null;
        $txnId = $obj['id'] ?? uniqid('paymob_txn_');

        return [
            'success' => true,
            'is_verified' => true,
            'status' => $success ? 'completed' : 'failed',
            'order_id' => $orderId,
            'transaction_id' => (string) $txnId,
        ];
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(PaymobPayment $payment, string $sessionId): string
    {
        if ($payment->isSimulationMode() || str_starts_with($sessionId, 'paymob_sim_token_')) {
            return route('oman_payments.simulation.paymob', ['session_id' => $sessionId]);
        }

        $iframeId = $payment->getConfigData('iframe_id');

        return self::BASE_URL . "/api/acceptance/iframes/{$iframeId}?payment_token={$sessionId}";
    }
}
