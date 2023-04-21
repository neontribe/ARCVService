<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\StateToken;
use App\Trader;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class PaymentsController extends Controller
{
    /** Lightweight check for outstanding payments to highlight in dashboard
     * @param $idkyet
     * @return bool
     */
    public static function checkIfOutstandingPayments(): bool
    {
        $date = Carbon::now()->subDays(7)->startOfDay();

        $payments = DB::table('state_tokens')
            ->where('created_at', '>', $date)
            ->whereNull('admin_user_id')
            ->count();
        if ($payments > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function index()
    {
        $pendingPaymentData = $this::getPaymentsPast7Days();
//       $reimbursedPaymentData = $this::getPaymentsPast7Days('reimbursed',Carbon::now()->subDays(7));
        return view('service.payments.index', ['pending' => $pendingPaymentData]);
//        return view('service.payments.index',['pending'=>$pendingPaymentData, 'reimbursed'=>$reimbursedPaymentData]);
    }

    /**
     * List Payments
     *
     * @return array
     */
    public static function getPaymentsPast7Days()
    {
        //set the period we want scoped
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        //get all the StateTokens for unpaid (pending) payment requests in the past 7 days
        // (in theory nothing is ever unpaid for that long anyway)
        $pendingTokens = StateToken::whereNotNull('user_id')
            ->whereNull('admin_user_id')
            ->where('created_at', '>', $sevenDaysAgo)
            ->get();

        $pendingResults = [];
        foreach ($pendingTokens as $stateToken) {

            $voucherStates = $stateToken->voucherStates()->get();
            $pendingResults[$stateToken->uuid] = [];
            $pendingResults[$stateToken->uuid]['uname'] = $stateToken->user()->name ?? 'unknown';
            $pendingResults[$stateToken->uuid]['total'] = count($voucherStates);

            //Get ALL of the VoucherStates that are related to this StateToken
            //As need to count them (proxy for total vouchers) and also use them to get to other attributes on related models

            //Get all the attributes we need via each voucherState
            foreach ($voucherStates as $voucherState) {
                //These are the main headers; check once and then take that going forward
                $pendingResults[$stateToken->uuid]['tname'] = $pendingResults[$stateToken->uuid]['tname'] ?? $voucherState->voucher->trader->name;
                $pendingResults[$stateToken->uuid]['tname'] = $pendingResults[$stateToken->uuid]['mname'] ?? $voucherState->voucher->trader->market->name;
                $pendingResults[$stateToken->uuid]['tname'] = $pendingResults[$stateToken->uuid]['mspon'] ?? $voucherState->voucher->trader->market->sponsor->name;

                $areaList = $pendingResults[$stateToken->uuid]['voucherareas'] ?? [];
                $areaList[$voucherState->voucher->sponsor->name] += 1;
                $pendingResults[$stateToken->uuid]['voucherareas'] = $areaList;
            }
        }
        return $pendingResults;
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
            if (!empty($vouchers)) {
                $trader = Trader::find($vouchers[0]->trader_id)->name;
            }
        }

        return view('service.payments.paymentRequest', [
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
            $success = true;
            foreach ($vouchers as $v) {
                if ($v->transitionAllowed('payout')) {
                    $v->applyTransition('payout');
                } else {
                    Log::info('Failure Processing Payout Transition');
                    $success = false;
                    break;
                }
            }
            if ($success) {
                $state_token->admin_user_id = Auth::user()->id;
                $state_token->save();
            }
        }
        return redirect()->route('admin.payments.index')->with('notification', 'Vouchers Paid!');
    }
}
