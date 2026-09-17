<?php

namespace NumbersNebula\OmanPayments\Models;

use Illuminate\Database\Eloquent\Model;

class OmanPaymentWebhook extends Model
{
    /**
     * Table name.
     */
    protected $table = 'oman_payment_webhooks';

    /**
     * Fillable fields.
     */
    protected $fillable = [
        'gateway',
        'event_type',
        'payload',
        'signature',
        'is_verified',
        'processed',
        'error_log',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'payload' => 'array',
        'is_verified' => 'boolean',
        'processed' => 'boolean',
    ];
}
