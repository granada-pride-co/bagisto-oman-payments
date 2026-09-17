<?php

namespace NumbersNebula\OmanPayments\Payment;

use Illuminate\Support\Facades\Storage;
use NumbersNebula\OmanPayments\Services\AmwalService;

class AmwalPayment extends AbstractOmanPayment
{
    /**
     * Payment method code.
     */
    protected $code = 'oman_amwal';

    /**
     * Create payment instance.
     */
    public function __construct(
        protected AmwalService $amwalService
    ) {}

    /**
     * Check if gateway has valid credentials.
     */
    public function hasValidCredentials(): bool
    {
        return ! empty($this->getConfigData('merchant_id'))
            && ! empty($this->getConfigData('secret_key'));
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->getConfigData('title') ?? trans('oman_payments::app.amwal.title');
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->getConfigData('description') ?? trans('oman_payments::app.amwal.description');
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

        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><rect width="60" height="60" rx="12" fill="%230284c7"/><text x="50%" y="54%" font-family="Arial,sans-serif" font-size="14" font-weight="bold" fill="white" dominant-baseline="middle" text-anchor="middle">AMW</text></svg>';
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(array $params): array
    {
        return $this->amwalService->initiateSession($this, $params);
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(string $transactionId, array $requestParams): array
    {
        return $this->amwalService->verifyPayment($this, $transactionId, $requestParams);
    }

    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(array $payload, ?string $signature): array
    {
        return $this->amwalService->handleWebhook($this, $payload, $signature);
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(string $sessionId): string
    {
        return $this->amwalService->getIframeUrl($this, $sessionId);
    }
}
