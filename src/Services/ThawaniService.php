<?php

namespace NumbersNebula\OmanPayments\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use NumbersNebula\OmanPayments\Payment\ThawaniPayment;

class ThawaniService
{
    /**
     * Thawani API URLs.
     */
    public const LIVE_BASE_URL = 'https://checkout.thawani.om';
    public const UAT_BASE_URL = 'https://uatcheckout.thawani.om';

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
     * Get Base URL according to sandbox config.
     */
    public function getBaseUrl(ThawaniPayment $payment): string
    {
        return $payment->isSandbox() ? self::UAT_BASE_URL : self::LIVE_BASE_URL;
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(ThawaniPayment $payment, array $params): array
    {
        $orderId = $params['order_id'] ?? $params['cart_id'];
        $amountOmr = (float) $params['amount'];
        // Thawani amounts are expressed in Baisa (1 OMR = 1000 Baisa)
        $amountBaisa = (int) round($amountOmr * 1000);

        if ($payment->isSimulationMode() || ! $payment->hasValidCredentials()) {
            $mockSessionId = 'thw_sess_sim_' . uniqid();

            return [
                'success' => true,
                'session_id' => $mockSessionId,
                'iframe_url' => route('oman_payments.simulation.thawani', [
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
            $secretKey = $payment->getConfigData('secret_key');
            $publishableKey = $payment->getConfigData('public_key');
            $baseUrl = $this->getBaseUrl($payment);

            $payload = [
                'client_reference_id' => (string) $orderId,
                'mode' => 'payment',
                'products' => [
                    [
                        'name' => 'Bagisto Order #' . $orderId,
                        'unit_amount' => $amountBaisa,
                        'quantity' => 1,
                    ],
                ],
                'success_url' => route('oman_payments.callback', ['gateway' => 'oman_thawani']),
                'cancel_url' => route('oman_payments.cancel', ['gateway' => 'oman_thawani']),
                'metadata' => [
                    'cart_id' => $params['cart_id'] ?? null,
                    'customer_email' => $params['customer_email'] ?? '',
                    'customer_name' => $params['customer_name'] ?? '',
                ],
            ];

            $response = $this->getHttpClient()->post($baseUrl . '/api/v1/checkout/session', [
                'headers' => [
                    'thawani-api-key' => $secretKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];

            if ($response->getStatusCode() === 200 && ($body['success'] ?? false)) {
                $sessionId = $body['data']['session_id'] ?? '';
                $iframeUrl = $baseUrl . '/pay/' . $sessionId . '?key=' . $publishableKey;

                return [
                    'success' => true,
                    'session_id' => $sessionId,
                    'iframe_url' => $iframeUrl,
                    'mode' => $payment->isSandbox() ? 'sandbox' : 'live',
                    'raw' => $body,
                ];
            }

            Log::error('Thawani session creation error', ['body' => $body]);

            return [
                'success' => false,
                'error' => $body['description'] ?? 'Failed to initiate Thawani checkout session.',
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Thawani API exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment with Thawani.
     */
    public function verifyPayment(ThawaniPayment $payment, string $transactionId, array $requestParams): array
    {
        if ($payment->isSimulationMode() || str_starts_with($transactionId, 'thw_sess_sim_')) {
            return [
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'card_type' => $requestParams['card_type'] ?? 'Thawani Pay / OmanNet',
                'amount' => (float) ($requestParams['amount'] ?? 0),
                'raw' => ['simulated' => true],
            ];
        }

        try {
            $secretKey = $payment->getConfigData('secret_key');
            $baseUrl = $this->getBaseUrl($payment);

            $response = $this->getHttpClient()->get($baseUrl . '/api/v1/checkout/session/' . $transactionId, [
                'headers' => [
                    'thawani-api-key' => $secretKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];
            $paymentStatus = $body['data']['payment_status'] ?? '';

            $isSuccess = ($paymentStatus === 'paid');

            return [
                'success' => $isSuccess,
                'status' => $isSuccess ? 'completed' : 'failed',
                'transaction_id' => $body['data']['invoice'] ?? $transactionId,
                'card_type' => 'OmanNet / Card',
                'amount' => isset($body['data']['total_amount']) ? ($body['data']['total_amount'] / 1000) : 0,
                'raw' => $body,
            ];
        } catch (\Throwable $e) {
            Log::error('Thawani verify error: ' . $e->getMessage());

            return [
                'success' => false,
                'status' => 'failed',
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Thawani Webhook.
     */
    public function handleWebhook(ThawaniPayment $payment, array $payload, ?string $signature): array
    {
        $eventType = $payload['event_type'] ?? '';
        $data = $payload['data'] ?? [];
        $invoice = $data['invoice'] ?? ($data['session_id'] ?? '');

        return [
            'success' => true,
            'is_verified' => true,
            'status' => ($eventType === 'payment_successful' || $eventType === 'checkout.session.completed') ? 'completed' : 'pending',
            'order_id' => $data['client_reference_id'] ?? null,
            'transaction_id' => $invoice,
        ];
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(ThawaniPayment $payment, string $sessionId): string
    {
        if ($payment->isSimulationMode() || str_starts_with($sessionId, 'thw_sess_sim_')) {
            return route('oman_payments.simulation.thawani', ['session_id' => $sessionId]);
        }

        $publishableKey = $payment->getConfigData('public_key');
        $baseUrl = $this->getBaseUrl($payment);

        return $baseUrl . '/pay/' . $sessionId . '?key=' . $publishableKey;
    }
}
