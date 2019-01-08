<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\StateToken;
use App\Trader;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:store');
    }

    /** Get a specific payment request by link
     * @param $paymentUuid
     * @return mixed
     */
    public function show($paymentUuid)
    {
        // Initialise
        $vouchers = [];
        $trader = "trader";
        $number_to_pay = 0;

        // Find the StateToken of a given uuid
        $state_token = StateToken::where('uuid', $paymentUuid)->first();
        if ($state_token !== null) {

            // Get the VoucherStates with this StateToken
            $voucher_states = $state_token
                ->voucherStates()
                ->get();

            // Get the voucher codes of states TODO - better
            foreach ($voucher_states as $voucher_state) {
                $vouchers[] = $voucher_state
                    ->voucher()
                    ->first();
            }

            // Count the payable vouchers
            $number_to_pay = collect($vouchers)
                ->where('currentstate', 'payment_pending')
                ->count();

            // Get the trader's name
            if(!empty($vouchers)) {
                $trader = Trader::find($vouchers{0}->trader_id)->name;
            }
        }

        return view('store.payment_request', [
            'state_token' => $state_token,
            'vouchers' => $vouchers,
            'trader' => $trader,
            'number_to_pay' => $number_to_pay,
        ]);
    }

    /** Pay a specific payment request by link
     * @param $paymentUuid
     * @return mixed
     */
    public function update($paymentUuid)
    {
        // Initialise
        $vouchers = [];
        $trader = "trader";
        $number_to_pay = 0;

        // Find the StateToken of a given uuid
        $state_token = StateToken::where('uuid', $paymentUuid)->first();
        if ($state_token !== null) {

            // Get the VoucherStates with this StateToken
            $voucher_states = $state_token
                ->voucherStates()
                ->get();

            // Get the voucher codes of states TODO - better
            foreach ($voucher_states as $voucher_state) {
                $voucher = $voucher_state
                    ->voucher()
                    ->first();

                $vouchers[] = $voucher;
            }

            // Transition the vouchers
            foreach ($vouchers as $v) {
                $v->applyTransition('payout');
            }

            // Count the payable vouchers
            $number_to_pay = collect($vouchers)
                ->where('currentstate', 'payment_pending')
                ->count();

            // Get the trader's name
            if(!empty($vouchers)) {
                $trader = Trader::find($vouchers{0}->trader_id)->name;
            }

        }

        // voucher transition to paid
        return view('store.payment_request', [
            'state_token' => $state_token,
            'vouchers' => $vouchers,
            'trader' => $trader,
            'number_to_pay' => $number_to_pay,
        ]);
    }
}
