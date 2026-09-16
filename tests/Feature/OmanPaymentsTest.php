<?php

namespace NumbersNebula\OmanPayments\Tests\Feature;

use NumbersNebula\OmanPayments\Payment\ThawaniPayment;
use NumbersNebula\OmanPayments\Payment\BankMuscatPayment;
use NumbersNebula\OmanPayments\Payment\AmwalPayment;
use NumbersNebula\OmanPayments\Payment\PaymobPayment;
use NumbersNebula\OmanPayments\Models\OmanPaymentTransaction;
use NumbersNebula\OmanPayments\Models\OmanPaymentWebhook;
use NumbersNebula\OmanPayments\Tests\OmanPaymentsTestCase;

class OmanPaymentsTest extends OmanPaymentsTestCase
{
    /**
     * Test all 4 gateways are registered in Bagisto configuration.
     */
    public function test_all_four_gateways_are_registered_in_config(): void
    {
        $methods = config('payment_methods');

        $this->assertArrayHasKey('oman_thawani', $methods);
        $this->assertArrayHasKey('oman_bankmuscat', $methods);
        $this->assertArrayHasKey('oman_amwal', $methods);
        $this->assertArrayHasKey('oman_paymob', $methods);

        $this->assertEquals(ThawaniPayment::class, $methods['oman_thawani']['class']);
        $this->assertEquals(BankMuscatPayment::class, $methods['oman_bankmuscat']['class']);
        $this->assertEquals(AmwalPayment::class, $methods['oman_amwal']['class']);
        $this->assertEquals(PaymobPayment::class, $methods['oman_paymob']['class']);
    }

    /**
     * Test gateway instances and availability in simulation mode.
     */
    public function test_gateways_instantiate_and_are_available(): void
    {
        $gateways = [
            'oman_thawani' => ThawaniPayment::class,
            'oman_bankmuscat' => BankMuscatPayment::class,
            'oman_amwal' => AmwalPayment::class,
            'oman_paymob' => PaymobPayment::class,
        ];

        foreach ($gateways as $code => $class) {
            $instance = app($class);
            $this->assertEquals($code, $instance->getCode());
            $this->assertTrue($instance->isAvailable());
            $this->assertStringContainsString($code, $instance->getRedirectUrl());
        }
    }

    /**
     * Test session initiation returns valid iframe URLs for all 4 gateways.
     */
    public function test_session_initiation_generates_iframe_urls(): void
    {
        $params = [
            'cart_id' => 999,
            'order_id' => 888,
            'amount' => 15.500,
            'currency' => 'OMR',
            'customer_name' => 'Salim Al-Hinai',
            'customer_email' => 'salim@example.om',
        ];

        $gateways = ['oman_thawani', 'oman_bankmuscat', 'oman_amwal', 'oman_paymob'];

        foreach ($gateways as $code) {
            $class = config("payment_methods.{$code}.class");
            $instance = app($class);

            $result = $instance->initiateSession($params);

            $this->assertTrue($result['success']);
            $this->assertNotEmpty($result['session_id']);
            $this->assertNotEmpty($result['iframe_url']);
            $this->assertStringContainsString('simulation', $result['iframe_url']);
        }
    }

    /**
     * Test verification logic for simulated transactions.
     */
    public function test_simulated_payment_verification(): void
    {
        $gateways = ['oman_thawani', 'oman_bankmuscat', 'oman_amwal', 'oman_paymob'];

        foreach ($gateways as $code) {
            $class = config("payment_methods.{$code}.class");
            $instance = app($class);

            $verification = $instance->verifyPayment('sim_test_session_id', [
                'amount' => 20.000,
                'card_type' => 'OmanNet Debit',
            ]);

            $this->assertTrue($verification['success']);
            $this->assertEquals('completed', $verification['status']);
            $this->assertNotEmpty($verification['card_type']);
        }
    }

    /**
     * Test simulation iframe views return HTTP 200.
     */
    public function test_simulation_iframe_endpoints_return_ok(): void
    {
        $routes = [
            route('oman_payments.simulation.thawani', ['session_id' => 'test_thw']),
            route('oman_payments.simulation.bankmuscat', ['session_id' => 'test_bm']),
            route('oman_payments.simulation.amwal', ['session_id' => 'test_amwal']),
            route('oman_payments.simulation.paymob', ['session_id' => 'test_paymob']),
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
        }
    }

    /**
     * Test webhook endpoint logs payload and updates records.
     */
    public function test_webhook_logging_and_handling(): void
    {
        $payload = [
            'event_type' => 'payment_successful',
            'data' => [
                'session_id' => 'thw_webhook_test_123',
                'invoice' => 'INV_OM_12345',
                'client_reference_id' => 'ORDER_9999',
            ],
        ];

        $response = $this->postJson(route('oman_payments.webhook', ['gateway' => 'oman_thawani']), $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('oman_payment_webhooks', [
            'gateway' => 'oman_thawani',
            'event_type' => 'payment_successful',
            'is_verified' => 1,
        ]);
    }

    /**
     * Test full end-to-end checkout flow: redirect -> iframe -> callback -> order & invoice creation.
     */
    public function test_end_to_end_checkout_creates_order_and_invoice(): void
    {
        $cart = $this->createCartWithItems('oman_thawani');
        $this->actingAs($cart->customer);

        // Step 1: Call redirect to obtain iframe wrapper
        $redirectResponse = $this->get(route('oman_payments.redirect', ['gateway' => 'oman_thawani']));
        $redirectResponse->assertStatus(200);
        $redirectResponse->assertViewIs('oman_payments::iframe-wrapper');

        $transaction = OmanPaymentTransaction::where('cart_id', $cart->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('pending', $transaction->status);

        // Step 2: Call callback with successful transaction
        $callbackResponse = $this->get(route('oman_payments.callback', [
            'gateway' => 'oman_thawani',
            'session_id' => $transaction->session_id,
            'transaction_id' => $transaction->transaction_id,
            'card_type' => 'Thawani Pay / OmanNet',
        ]));

        $callbackResponse->assertRedirect(route('shop.checkout.onepage.success'));

        // Step 3: Assert transaction updated, order created, invoice created
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertNotNull($transaction->order_id);

        $this->assertDatabaseHas('orders', [
            'id' => $transaction->order_id,
            'status' => 'processing',
        ]);

        $this->assertDatabaseHas('invoices', [
            'order_id' => $transaction->order_id,
            'state' => 'paid',
        ]);

        $this->assertDatabaseHas('order_transactions', [
            'order_id' => $transaction->order_id,
            'status' => 'captured',
            'payment_method' => 'oman_thawani',
        ]);
    }
}
