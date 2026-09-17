<?php

namespace NumbersNebula\OmanPayments\Payment;

use Illuminate\Support\Facades\Storage;
use NumbersNebula\OmanPayments\Services\ThawaniService;

class ThawaniPayment extends AbstractOmanPayment
{
    /**
     * Payment method code.
     */
    protected $code = 'oman_thawani';

    /**
     * Create payment instance.
     */
    public function __construct(
        protected ThawaniService $thawaniService
    ) {}

    /**
     * Check if gateway has valid credentials.
     */
    public function hasValidCredentials(): bool
    {
        return ! empty($this->getConfigData('public_key'))
            && ! empty($this->getConfigData('secret_key'));
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->getConfigData('title') ?? trans('oman_payments::app.thawani.title');
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->getConfigData('description') ?? trans('oman_payments::app.thawani.description');
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

        return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><rect width="60" height="60" rx="12" fill="%2316a34a"/><circle cx="30" cy="30" r="16" fill="none" stroke="white" stroke-width="4"/><circle cx="30" cy="30" r="7" fill="white"/></svg>';
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(array $params): array
    {
        return $this->thawaniService->initiateSession($this, $params);
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(string $transactionId, array $requestParams): array
    {
        return $this->thawaniService->verifyPayment($this, $transactionId, $requestParams);
    }

    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(array $payload, ?string $signature): array
    {
        return $this->thawaniService->handleWebhook($this, $payload, $signature);
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(string $sessionId): string
    {
        return $this->thawaniService->getIframeUrl($this, $sessionId);
    }
}
