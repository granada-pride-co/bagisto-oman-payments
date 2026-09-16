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
        Schema::create('oman_payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50)->index();
            $table->string('event_type', 100)->nullable()->index();
            $table->json('payload');
            $table->string('signature', 255)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('processed')->default(false);
            $table->text('error_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oman_payment_webhooks');
    }
};
