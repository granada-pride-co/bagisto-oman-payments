<?php

namespace NumbersNebula\OmanPayments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulationController extends Controller
{
    /**
     * Thawani Pay simulated iFrame interface.
     */
    public function thawani(Request $request): View
    {
        return view('oman_payments::simulation.thawani-iframe', [
            'sessionId' => $request->input('session_id', 'thw_sim_' . uniqid()),
            'amount' => (float) $request->input('amount', 25.500),
            'currency' => $request->input('currency', 'OMR'),
            'callbackUrl' => route('oman_payments.callback', ['gateway' => 'oman_thawani']),
            'cancelUrl' => route('oman_payments.cancel', ['gateway' => 'oman_thawani']),
        ]);
    }

    /**
     * Bank Muscat SmartPay simulated iFrame interface.
     */
    public function bankmuscat(Request $request): View
    {
        return view('oman_payments::simulation.bankmuscat-iframe', [
            'sessionId' => $request->input('session_id', 'SESSION_BM_SIM_' . uniqid()),
            'amount' => (float) $request->input('amount', 25.500),
            'currency' => $request->input('currency', 'OMR'),
            'callbackUrl' => route('oman_payments.callback', ['gateway' => 'oman_bankmuscat']),
            'cancelUrl' => route('oman_payments.cancel', ['gateway' => 'oman_bankmuscat']),
        ]);
    }

    /**
     * AmwalPay simulated iFrame interface.
     */
    public function amwal(Request $request): View
    {
        return view('oman_payments::simulation.amwal-iframe', [
            'sessionId' => $request->input('session_id', 'amwal_sess_sim_' . uniqid()),
            'amount' => (float) $request->input('amount', 25.500),
            'currency' => $request->input('currency', 'OMR'),
            'callbackUrl' => route('oman_payments.callback', ['gateway' => 'oman_amwal']),
            'cancelUrl' => route('oman_payments.cancel', ['gateway' => 'oman_amwal']),
        ]);
    }

    /**
     * Paymob Oman simulated iFrame interface.
     */
    public function paymob(Request $request): View
    {
        return view('oman_payments::simulation.paymob-iframe', [
            'sessionId' => $request->input('session_id', 'paymob_sim_token_' . uniqid()),
            'amount' => (float) $request->input('amount', 25.500),
            'currency' => $request->input('currency', 'OMR'),
            'callbackUrl' => route('oman_payments.callback', ['gateway' => 'oman_paymob']),
            'cancelUrl' => route('oman_payments.cancel', ['gateway' => 'oman_paymob']),
        ]);
    }
}
