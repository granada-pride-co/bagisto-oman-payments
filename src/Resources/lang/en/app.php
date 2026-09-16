<?php

return [
    'title' => 'Omani Payment Gateways',
    'security_badge' => 'Secure payment protected with 256-bit encryption compliant with Central Bank of Oman (CBO)',
    'powered_by' => 'Oman National Payment Switch',
    'omannet_protected' => 'Compatible with OmanNet debit cards, Visa, and Mastercard',
    'simulation_notice' => 'Simulation mode is active - you can complete test payments using the interactive buttons inside the iFrame without real deductions.',
    'cancel_payment' => 'Cancel and return to store',
    'processing' => 'Preparing payment gateway...',

    'fields' => [
        'simulation_mode' => 'Interactive Simulation Mode',
        'simulation_mode_info' => 'When enabled, an interactive mock payment interface is loaded in the iFrame for full testing without live bank credentials.',
    ],

    'thawani' => [
        'title' => 'Thawani Pay',
        'description' => 'Pay securely using Thawani e-Wallet or debit/credit cards directly via iFrame.',
        'system_title' => 'Thawani Pay',
        'system_info' => 'Omani payment gateway licensed by Central Bank of Oman (CBO).',
        'public_key' => 'Publishable Key',
        'secret_key' => 'Secret Key',
    ],

    'bankmuscat' => [
        'title' => 'Bank Muscat SmartPay',
        'description' => 'Pay securely through Bank Muscat SmartPay hosted MPGS iFrame.',
        'system_title' => 'Bank Muscat SmartPay (MPGS)',
        'system_info' => 'Enterprise payment gateway by Bank Muscat using Hosted Checkout iFrame.',
        'merchant_id' => 'Merchant ID',
        'api_password' => 'API Password',
        'gateway_host' => 'Gateway Host',
        'gateway_host_info' => 'Default: bankmuscat.gateway.mastercard.com',
        'api_version' => 'API Version',
    ],

    'amwal' => [
        'title' => 'AmwalPay - OmanNet',
        'description' => 'Pay securely with OmanNet Debit Cards or Credit Cards via Amwal SmartBox iFrame.',
        'system_title' => 'AmwalPay',
        'system_info' => 'Omani payment gateway supporting direct OmanNet card routing.',
        'merchant_id' => 'Merchant ID',
        'terminal_id' => 'Terminal ID',
        'secret_key' => 'Secret Key for Secure Hash',
    ],

    'paymob' => [
        'title' => 'Paymob Oman',
        'description' => 'Fast checkout supporting OmanNet debit cards, Visa, and Mastercard via embedded iFrame.',
        'system_title' => 'Paymob Oman',
        'system_info' => 'Comprehensive payment gateway supporting Omani debit & credit cards.',
        'api_key' => 'API Key',
        'integration_id' => 'Integration ID',
        'iframe_id' => 'iFrame ID',
        'hmac_secret' => 'HMAC Secret',
    ],

    'messages' => [
        'invalid_gateway' => 'The selected payment gateway is invalid or not enabled.',
        'cart_empty' => 'Shopping cart is empty. Please add items to continue.',
        'session_error' => 'Unable to initiate payment session. Please check gateway credentials.',
        'generic_error' => 'An error occurred during payment processing. Please try again.',
        'invalid_transaction' => 'Invalid or expired transaction identifier.',
        'payment_failed' => 'Payment was declined or failed at the bank.',
        'cart_expired' => 'Cart has expired. Please restart checkout.',
        'payment_cancelled' => 'Payment was cancelled by the customer.',
    ],
];
