<?php

namespace NumbersNebula\OmanPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

class OmanPaymentTransaction extends Model
{
    /**
     * Table name.
     */
    protected $table = 'oman_payment_transactions';

    /**
     * Fillable fields.
     */
    protected $fillable = [
        'transaction_id',
        'gateway',
        'order_id',
        'cart_id',
        'session_id',
        'amount',
        'currency',
        'status',
        'payment_mode',
        'card_type',
        'iframe_url',
        'response_data',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'amount' => 'decimal:4',
        'response_data' => 'array',
    ];

    /**
     * Associated order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
