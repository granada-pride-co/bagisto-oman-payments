<?php

use NumbersNebula\OmanPayments\Payment\ThawaniPayment;
use NumbersNebula\OmanPayments\Payment\BankMuscatPayment;
use NumbersNebula\OmanPayments\Payment\AmwalPayment;
use NumbersNebula\OmanPayments\Payment\PaymobPayment;

return [
    'oman_thawani' => [
        'code' => 'oman_thawani',
        'title' => 'Thawani Pay (بوابة ثواني)',
        'description' => 'Pay securely using Thawani Wallet or Debit/Credit Cards via iFrame',
        'class' => ThawaniPayment::class,
        'active' => true,
        'sandbox' => true,
        'simulation_mode' => true,
        'sort' => 10,
    ],
    'oman_bankmuscat' => [
        'code' => 'oman_bankmuscat',
        'title' => 'Bank Muscat SmartPay (بنك مسقط)',
        'description' => 'Pay securely with Bank Muscat SmartPay MPGS Hosted iFrame',
        'class' => BankMuscatPayment::class,
        'active' => true,
        'sandbox' => true,
        'simulation_mode' => true,
        'sort' => 11,
    ],
    'oman_amwal' => [
        'code' => 'oman_amwal',
        'title' => 'AmwalPay - OmanNet (أموال باي)',
        'description' => 'Pay securely with OmanNet Debit Card via Amwal SmartBox iFrame',
        'class' => AmwalPayment::class,
        'active' => true,
        'sandbox' => true,
        'simulation_mode' => true,
        'sort' => 12,
    ],
    'oman_paymob' => [
        'code' => 'oman_paymob',
        'title' => 'Paymob Oman (باي موب عمان)',
        'description' => 'Pay using OmanNet Debit Cards, Visa, Mastercard via Paymob iFrame',
        'class' => PaymobPayment::class,
        'active' => true,
        'sandbox' => true,
        'simulation_mode' => true,
        'sort' => 13,
    ],
];
