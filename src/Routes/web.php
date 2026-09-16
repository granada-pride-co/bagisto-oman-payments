<?php

use Illuminate\Support\Facades\Route;
use NumbersNebula\OmanPayments\Http\Controllers\OmanPaymentController;
use NumbersNebula\OmanPayments\Http\Controllers\SimulationController;
use NumbersNebula\OmanPayments\Http\Controllers\WebhookController;

Route::group(['middleware' => ['web']], function () {
    /**
     * Checkout Redirection & Callback Routes.
     */
    Route::prefix('oman-payments')->group(function () {
        Route::get('redirect/{gateway}', [OmanPaymentController::class, 'redirect'])
            ->name('oman_payments.redirect');

        Route::get('callback/{gateway}', [OmanPaymentController::class, 'callback'])
            ->name('oman_payments.callback');

        Route::post('callback/{gateway}', [OmanPaymentController::class, 'callback']);

        Route::get('cancel/{gateway}', [OmanPaymentController::class, 'cancel'])
            ->name('oman_payments.cancel');

        /**
         * Simulation Mock iFrames (for testing without live bank credentials).
         */
        Route::prefix('simulation')->group(function () {
            Route::get('thawani', [SimulationController::class, 'thawani'])
                ->name('oman_payments.simulation.thawani');

            Route::get('bankmuscat', [SimulationController::class, 'bankmuscat'])
                ->name('oman_payments.simulation.bankmuscat');

            Route::get('amwal', [SimulationController::class, 'amwal'])
                ->name('oman_payments.simulation.amwal');

            Route::get('paymob', [SimulationController::class, 'paymob'])
                ->name('oman_payments.simulation.paymob');
        });
    });
});

/**
 * Gateway Webhooks (without web CSRF middleware).
 */
Route::post('oman-payments/webhook/{gateway}', [WebhookController::class, 'handle'])
    ->name('oman_payments.webhook');
