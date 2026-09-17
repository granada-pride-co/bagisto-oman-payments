<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oman_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->index();
            $table->string('gateway', 50)->index(); // oman_thawani, oman_bankmuscat, oman_amwal, oman_paymob
            $table->unsignedInteger('order_id')->nullable()->index();
            $table->unsignedInteger('cart_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->decimal('amount', 12, 4)->default(0.0000);
            $table->string('currency', 10)->default('OMR');
            $table->string('status', 30)->default('pending')->index(); // pending, completed, failed, cancelled
            $table->string('payment_mode', 20)->default('live'); // live, sandbox, simulation
            $table->string('card_type', 50)->nullable(); // OmanNet, Visa, Mastercard, Thawani Pay
            $table->text('iframe_url')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oman_payment_transactions');
    }
};
