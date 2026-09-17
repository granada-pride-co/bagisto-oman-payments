<?php

namespace NumbersNebula\OmanPayments\Payment;

use Illuminate\Support\Facades\Storage;
use NumbersNebula\OmanPayments\Services\PaymobService;

class PaymobPayment extends AbstractOmanPayment
{
    /**
     * Payment method code.
     */
    protected $code = 'oman_paymob';

    /**
     * Create payment instance.
     */
    public function __construct(
        protected PaymobService $paymobService
    ) {}

    /**
     * Check if gateway has valid credentials.
     */
    public function hasValidCredentials(): bool
    {
        return ! empty($this->getConfigData('api_key'))
            && ! empty($this->getConfigData('iframe_id'))
            && ! empty($this->getConfigData('integration_id'));
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->getConfigData('title') ?? trans('oman_payments::app.paymob.title');
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->getConfigData('description') ?? trans('oman_payments::app.paymob.description');
    }

    /**
     * Returns payment method image.
     */
    public function getImage(): string
    {
        $image = $this->getConfigData('image');

        if ($image) {
            return Storage::url($image);
        }

        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><rect width="60" height="60" rx="12" fill="%232563eb"/><text x="50%" y="54%" font-family="Arial,sans-serif" font-size="14" font-weight="bold" fill="white" dominant-baseline="middle" text-anchor="middle">PAY</text></svg>';
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(array $params): array
    {
        return $this->paymobService->initiateSession($this, $params);
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(string $transactionId, array $requestParams): array
    {
        return $this->paymobService->verifyPayment($this, $transactionId, $requestParams);
    }

    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(array $payload, ?string $signature): array
    {
        return $this->paymobService->handleWebhook($this, $payload, $signature);
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(string $sessionId): string
    {
        return $this->paymobService->getIframeUrl($this, $sessionId);
    }
}
