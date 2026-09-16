<?php

namespace NumbersNebula\OmanPayments\Contracts;

interface OmanPaymentGatewayInterface
{
    /**
     * Get unique gateway code.
     */
    public function getGatewayCode(): string;

    /**
     * Initiate payment session with gateway and return iframe information.
     *
     * @param array $params [order_id, cart_id, amount, currency, customer_name, customer_email, return_url, cancel_url]
     * @return array [success => bool, session_id => string, iframe_url => string, raw => array]
     */
    public function initiateSession(array $params): array;

    /**
     * Verify payment status using transaction ID or callback payload.
     *
     * @param string $transactionId
     * @param array $requestParams
     * @return array [success => bool, status => string, transaction_id => string, card_type => string|null, amount => float, raw => array]
     */
    public function verifyPayment(string $transactionId, array $requestParams): array;

    /**
     * Handle incoming webhook notification from gateway.
     *
     * @param array $payload
     * @param string|null $signature
     * @return array [success => bool, is_verified => bool, status => string, order_id => string|null, transaction_id => string]
     */
    public function handleWebhook(array $payload, ?string $signature): array;

    /**
     * Check if gateway is operating in local interactive simulation mode.
     */
    public function isSimulationMode(): bool;

    /**
     * Generate or retrieve iFrame URL.
     */
    public function getIframeUrl(string $sessionId): string;
}
