<?php

namespace NumbersNebula\OmanPayments\Payment;

use Illuminate\Support\Facades\Storage;
use NumbersNebula\OmanPayments\Services\BankMuscatService;

class BankMuscatPayment extends AbstractOmanPayment
{
    /**
     * Payment method code.
     */
    protected $code = 'oman_bankmuscat';

    /**
     * Create payment instance.
     */
    public function __construct(
        protected BankMuscatService $bankMuscatService
    ) {}

    /**
     * Check if gateway has valid credentials.
     */
    public function hasValidCredentials(): bool
    {
        return ! empty($this->getConfigData('merchant_id'))
            && ! empty($this->getConfigData('api_password'));
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->getConfigData('title') ?? trans('oman_payments::app.bankmuscat.title');
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return $this->getConfigData('description') ?? trans('oman_payments::app.bankmuscat.description');
    }

    /**
     * Returns payment method image.
     */
    public function getImage(): string
    {
        $image = $this->getConfigData('image');

        return $image ? Storage::url($image) : bagisto_asset('images/bankmuscat.png', 'shop');
    }

    /**
     * Initiate payment session.
     */
    public function initiateSession(array $params): array
    {
        return $this->bankMuscatService->initiateSession($this, $params);
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(string $transactionId, array $requestParams): array
    {
        return $this->bankMuscatService->verifyPayment($this, $transactionId, $requestParams);
    }

    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(array $payload, ?string $signature): array
    {
        return $this->bankMuscatService->handleWebhook($this, $payload, $signature);
    }

    /**
     * Get iFrame URL.
     */
    public function getIframeUrl(string $sessionId): string
    {
        return $this->bankMuscatService->getIframeUrl($this, $sessionId);
    }
}
