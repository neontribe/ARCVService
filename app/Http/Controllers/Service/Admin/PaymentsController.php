<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\StateToken;
use App\Trader;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class PaymentsController extends Controller
{
    /** Lightweight check for outstanding payments to highlight in dashboard
     * @return bool
     */
    public static function checkIfOutstandingPayments(): bool
    {
        $date = Carbon::now()->subDays(7)->startOfDay();

        $payments = DB::table('state_tokens')
            ->where('created_at', '>', $date)
            ->whereNull('admin_user_id')
            ->count();

        return $payments > 0;
    }

    /**
     * Lists the payments paid and pending
     * @return Factory|View|Application
     */
    public function index(): Factory|View|Application
    {
        $pendingPaymentData = self::getStateTokensFromDate();
        $reimbursedPaymentData = self::getStateTokensFromDate(true);
        return view('service.payments.index', [
            'pending' => $pendingPaymentData,
            'reimbursed' => $reimbursedPaymentData,
        ]);
    }

    /**
     * List Payments
     * @param bool $paid
     * @param Carbon|null $date
     * @return array
     */
    public static function getStateTokensFromDate(bool $paid = false, Carbon $date = null): array
    {

        //set the period we want scoped
        $fromDate = $date ?? Carbon::now()->subDays(7)->startOfDay();
        //get all the StateTokens for unpaid (pending) payment requests in the past 7 days
        // (in theory nothing is ever unpaid for that long anyway)
        $tokens = StateToken::with([
            'user',
            'voucherStates',
            'voucherStates.voucher',
            'voucherStates.voucher.trader',
            'voucherStates.voucher.trader.market.sponsor',
            'voucherStates.voucher.sponsor',
        ])
            ->where('created_at', '>', $fromDate->format('Y-m-d'))
            ->whereNotNull('user_id')
            // if $paid = true will make this a NotNull, thereby getting paid things
            ->whereNull('admin_user_id', 'and', $paid)
            ->orderBy('created_at','desc')
            ->get();

        return self::makePaymentDataStructure($tokens);
    }

    /**
     * Constructs the Payment structure for our blade
     * @param Collection $tokens
     * @return array
     */
    public static function makePaymentDataStructure(Collection $tokens): array
    {
        $pendingResults = [];
        foreach ($tokens as $stateToken) {
            // start tracking this set of results
            $currentTokenResults = [];
            $currentTokenResults['requestedBy'] = $stateToken->user->name ?? 'unknown';

            // get the states for this token
            $voucherStates = $stateToken->voucherStates->all();
            // count 'em while we're here
            $currentTokenResults['vouchersTotal'] = count($voucherStates);

            //Get all the attributes we need via each voucherState
            foreach ($voucherStates as $voucherState) {
                $trader = $voucherState->voucher->trader;
                //These are the main headers; check once and then take that going forward
                $currentTokenResults['traderName'] ??= $trader->name;
                if (empty($currentTokenResults['traderName'])) {
                    \Log::warning("Bad voucher trader name: ", json_encode($voucherStates));
                    continue;
                }
                $currentTokenResults['marketName'] ??= $trader->market->name;
                $currentTokenResults['area'] ??= $trader->market->sponsor->name;

                $areaList = $currentTokenResults['voucherAreas'] ?? [];
                $areaName = $voucherState->voucher->sponsor->name;
                $areaList[$areaName] = isset($areaList[$areaName])
                    ? $areaList[$areaName] +=1
                    : 1;
                $currentTokenResults['voucherAreas'] = $areaList;
            }
            foreach ($pendingResults as $index => $result) {
                if (
                    empty($result['requestedBy']) ||
                    empty($result['vouchersTotal']) ||
                    empty($result['traderName']) ||
                    empty($result['marketName']) ||
                    empty($result['area']) ||
                    empty($result['voucherAreas'])
                ) {
                    \Log::error("Bad pending results at index " . $index . " - " . json_encode($result));
                    unset($pendingResults[$index]);
                }
            }

            // chuck that in the results array.
            $pendingResults[$stateToken->uuid] = $currentTokenResults;
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
            Log::info(sprintf(
                "%s: Processing %d vouchers, uuid=%s, user=%s(%d), admin user=%s(%d)",
                __CLASS__,
                count($vouchers),
                $state_token->uuid,
                $state_token->user?->name,
                $state_token->user?->id,
                Auth::user()->name,
                Auth::user()->id,
            ));
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
