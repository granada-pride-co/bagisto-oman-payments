<?php

namespace NumbersNebula\OmanPayments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use NumbersNebula\OmanPayments\Contracts\OmanPaymentGatewayInterface;
use NumbersNebula\OmanPayments\Models\OmanPaymentTransaction;
use NumbersNebula\OmanPayments\Payment\ThawaniPayment;
use NumbersNebula\OmanPayments\Payment\BankMuscatPayment;
use NumbersNebula\OmanPayments\Payment\AmwalPayment;
use NumbersNebula\OmanPayments\Payment\PaymobPayment;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Transformers\OrderResource;

class OmanPaymentController extends Controller
{
    /**
     * Map of gateway codes to classes.
     */
    protected array $gatewayMap = [
        'oman_thawani' => ThawaniPayment::class,
        'oman_bankmuscat' => BankMuscatPayment::class,
        'oman_amwal' => AmwalPayment::class,
        'oman_paymob' => PaymobPayment::class,
    ];

    /**
     * Create controller instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected InvoiceRepository $invoiceRepository,
    ) {}

    /**
     * Resolve payment gateway instance.
     */
    protected function getGatewayInstance(string $gatewayCode): ?OmanPaymentGatewayInterface
    {
        if (! isset($this->gatewayMap[$gatewayCode])) {
            return null;
        }

        return app($this->gatewayMap[$gatewayCode]);
    }

    /**
     * Redirect to the payment iFrame wrapper page.
     */
    public function redirect(Request $request, string $gateway): View|RedirectResponse
    {
        $gatewayInstance = $this->getGatewayInstance($gateway);

        if (! $gatewayInstance) {
            session()->flash('error', trans('oman_payments::app.messages.invalid_gateway'));
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', trans('oman_payments::app.messages.cart_empty'));
            return redirect()->route('shop.checkout.cart.index');
        }

        $currency = strtoupper($cart->base_currency_code ?? core()->getBaseCurrencyCode());

        try {
            $billingAddress = $cart->billing_address;
            $customerName = $billingAddress ? trim($billingAddress->first_name . ' ' . $billingAddress->last_name) : 'Guest Customer';
            $customerEmail = $billingAddress ? $billingAddress->email : ($cart->customer_email ?? 'guest@example.com');

            $params = [
                'cart_id' => $cart->id,
                'order_id' => 'CART_' . $cart->id . '_' . time(),
                'amount' => (float) $cart->base_grand_total,
                'currency' => $currency,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'billing_address' => $billingAddress ? $billingAddress->toArray() : [],
            ];

            $sessionResult = $gatewayInstance->initiateSession($params);

            if (! ($sessionResult['success'] ?? false)) {
                $errorMsg = $sessionResult['error'] ?? trans('oman_payments::app.messages.session_error');
                session()->flash('error', $errorMsg);
                return redirect()->route('shop.checkout.onepage.index');
            }

            $sessionId = $sessionResult['session_id'];
            $iframeUrl = $sessionResult['iframe_url'];

            // Store transaction record
            OmanPaymentTransaction::create([
                'transaction_id' => $sessionId,
                'gateway' => $gateway,
                'cart_id' => $cart->id,
                'session_id' => $sessionId,
                'amount' => (float) $cart->base_grand_total,
                'currency' => $currency,
                'status' => 'pending',
                'payment_mode' => $sessionResult['mode'] ?? 'live',
                'iframe_url' => $iframeUrl,
                'response_data' => $sessionResult['raw'] ?? [],
            ]);

            return view('oman_payments::iframe-wrapper', [
                'gateway' => $gateway,
                'gatewayTitle' => $gatewayInstance->getTitle(),
                'iframeUrl' => $iframeUrl,
                'sessionId' => $sessionId,
                'amount' => (float) $cart->base_grand_total,
                'currency' => $currency,
                'cartId' => $cart->id,
                'isSimulation' => $gatewayInstance->isSimulationMode(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Oman payment redirect failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', trans('oman_payments::app.messages.generic_error'));
            return redirect()->route('shop.checkout.onepage.index');
        }
    }

    /**
     * Handle return/callback from the gateway or iFrame postMessage.
     */
    public function callback(Request $request, string $gateway): RedirectResponse
    {
        $gatewayInstance = $this->getGatewayInstance($gateway);

        if (! $gatewayInstance) {
            session()->flash('error', trans('oman_payments::app.messages.invalid_gateway'));
            return redirect()->route('shop.checkout.cart.index');
        }

        $transactionId = $request->input('transaction_id')
            ?? $request->input('session_id')
            ?? $request->input('id')
            ?? $request->input('order_id');

        if (! $transactionId) {
            session()->flash('error', trans('oman_payments::app.messages.invalid_transaction'));
            return redirect()->route('shop.checkout.cart.index');
        }

        $verification = $gatewayInstance->verifyPayment($transactionId, $request->all());

        if (! ($verification['success'] ?? false)) {
            // Update transaction record if found
            OmanPaymentTransaction::where('transaction_id', $transactionId)
                ->orWhere('session_id', $transactionId)
                ->update(['status' => 'failed', 'response_data' => $verification['raw'] ?? []]);

            session()->flash('error', trans('oman_payments::app.messages.payment_failed'));
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (! $cart) {
            // Check if order was already created previously by webhook or concurrent callback
            $existingTxn = OmanPaymentTransaction::where('transaction_id', $transactionId)
                ->orWhere('session_id', $transactionId)
                ->first();

            if ($existingTxn && $existingTxn->order_id) {
                session()->flash('order_id', $existingTxn->order_id);
                return redirect()->route('shop.checkout.onepage.success');
            }

            session()->flash('error', trans('oman_payments::app.messages.cart_expired'));
            return redirect()->route('shop.checkout.cart.index');
        }

        return $this->processSuccessfulOrder($cart, $gateway, $transactionId, $verification);
    }

    /**
     * Process order completion, invoice and transaction.
     */
    protected function processSuccessfulOrder($cart, string $gateway, string $transactionId, array $verification): RedirectResponse
    {
        try {
            $orderData = (new OrderResource($cart))->jsonSerialize();
            $order = $this->orderRepository->create($orderData);

            if ($order->payment) {
                $order->payment->update([
                    'additional' => [
                        'status' => Invoice::STATUS_PAID,
                        'gateway' => $gateway,
                        'transaction_id' => $transactionId,
                        'card_type' => $verification['card_type'] ?? 'Oman Payment Card',
                    ],
                ]);
            }

            $this->orderRepository->update(['status' => Order::STATUS_PROCESSING], $order->id);

            // Generate invoice
            $invoiceData = $this->prepareInvoiceData($order->id);
            $invoice = null;
            if (! empty($invoiceData)) {
                $invoice = $this->invoiceRepository->create($invoiceData);
            }

            // Save order transaction
            $this->orderTransactionRepository->create([
                'transaction_id' => $transactionId,
                'status' => 'captured',
                'type' => $gateway,
                'payment_method' => $gateway,
                'order_id' => $order->id,
                'invoice_id' => $invoice ? $invoice->id : null,
                'amount' => $orderData['base_grand_total'] ?? 0,
                'data' => json_encode([
                    'gateway' => $gateway,
                    'transaction_id' => $transactionId,
                    'verification' => $verification,
                ]),
            ]);

            // Update Oman Payment Transaction record
            OmanPaymentTransaction::where('transaction_id', $transactionId)
                ->orWhere('session_id', $transactionId)
                ->update([
                    'order_id' => $order->id,
                    'status' => 'completed',
                    'card_type' => $verification['card_type'] ?? null,
                    'response_data' => $verification['raw'] ?? [],
                ]);

            Cart::deActivateCart();
            session()->flash('order_id', $order->id);

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Throwable $e) {
            Log::error('Failed to create order from Oman payment: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', trans('oman_payments::app.messages.generic_error'));
            return redirect()->route('shop.checkout.cart.index');
        }
    }

    /**
     * Handle payment cancellation.
     */
    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        $sessionId = $request->input('session_id') ?? $request->input('transaction_id');

        if ($sessionId) {
            OmanPaymentTransaction::where('session_id', $sessionId)
                ->orWhere('transaction_id', $sessionId)
                ->update(['status' => 'cancelled']);
        }

        session()->flash('warning', trans('oman_payments::app.messages.payment_cancelled'));

        return redirect()->route('shop.checkout.onepage.index');
    }

    /**
     * Prepare invoice items data.
     */
    protected function prepareInvoiceData(int $orderId): array
    {
        $order = $this->orderRepository->find($orderId);

        if (! $order) {
            return [];
        }

        $invoiceItems = [];

        foreach ($order->items as $item) {
            if ($item->qty_to_invoice > 0) {
                $invoiceItems[$item->id] = $item->qty_to_invoice;
            }
        }

        if (empty($invoiceItems)) {
            return [];
        }

        return [
            'order_id' => $order->id,
            'invoice' => [
                'items' => $invoiceItems,
            ],
        ];
    }
}
