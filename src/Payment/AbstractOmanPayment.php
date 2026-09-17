<?php

namespace NumbersNebula\OmanPayments\Payment;

use NumbersNebula\OmanPayments\Contracts\OmanPaymentGatewayInterface;
use Webkul\Payment\Payment\Payment;

abstract class AbstractOmanPayment extends Payment implements OmanPaymentGatewayInterface
{
    /**
     * Supported currencies for Omani gateways.
     */
    protected array $supportedCurrencies = ['OMR', 'USD', 'AED', 'SAR'];

    /**
     * Get redirect URL when customer places order or chooses payment.
     */
    public function getRedirectUrl(): string
    {
        return route('oman_payments.redirect', ['gateway' => $this->getCode()]);
    }

    /**
     * Retrieve information from payment configuration with fallback to default config.
     */
    public function getConfigData($field)
    {
        $value = parent::getConfigData($field);

        if ($value === null) {
            return config('payment_methods.'.$this->getCode().'.'.$field);
        }

        return $value;
    }

    /**
     * Check if payment method is available in current checkout.
     */
    public function isAvailable(): bool
    {
        if (! (bool) $this->getConfigData('active')) {
            return false;
        }

        if ($this->isSimulationMode()) {
            return true;
        }

        return $this->hasValidCredentials();
    }

    /**
     * Check if required credentials exist.
     */
    abstract public function hasValidCredentials(): bool;

    /**
     * Get gateway unique code.
     */
    public function getGatewayCode(): string
    {
        return $this->getCode();
    }

    /**
     * Check if simulation mode is enabled.
     */
    public function isSimulationMode(): bool
    {
        return (bool) $this->getConfigData('simulation_mode');
    }

    /**
     * Check if sandbox mode is enabled.
     */
    public function isSandbox(): bool
    {
        return (bool) $this->getConfigData('sandbox');
    }

    /**
     * Get supported currencies.
     */
    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * Check if currency is supported.
     */
    public function isCurrencySupported(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supportedCurrencies, true);
    }
}
