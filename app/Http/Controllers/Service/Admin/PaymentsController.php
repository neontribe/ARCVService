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
     * @return LengthAwarePaginator
     */
    public static function getPaymentsPast7Days()
    {
        //set the period we want scoped
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        //get all the StateTokens for unpaid (pending) payment requests in the past 7 days
        // (in theory nothing is ever unpaid for that long anyway)
        $pending = StateToken::whereNotNull('user_id')->whereNull('admin_user_id')->where('created_at', '>', $sevenDaysAgo)->get();
        //get all the StateTokens for payments recorded as PAID in the last seven days
//        $paid = StateToken::whereNotNull('user_id')->whereNotNull('admin_user_id')->where('created_at',>, $sevenDaysAgo)->get();
//        print("<pre>".print_r($pending,true)."</pre>");

        foreach ($pending as $stateToken) {
            //Get ALL of the VoucherStates that are related to this StateToken
            //As need to count them (proxy for total vouchers) and also use them to get to other attributes on related models
            $voucherStates = $stateToken->voucherStates()->get();
            //total //distinct voucher ids on voucher_state
            //              $voucher = $lastState->voucher;
            $voucherStatesOutput = [];
            //Get all the attributes we need via each voucherState
            foreach ($voucherStates as $voucherState) {
//              $voucherState = $paymentData[$voucherState->stuuid] ?? [];
                //add the uuid to each voucherState for use later
//                $voucherStatesOutput[$stateToken->uuid] = [];
                $voucherStatesOutput[$stateToken->uuid] = [];
                //These are the main headers
                $voucherStatesOutput[$stateToken->uuid]['tname'] = $voucherState->voucher->trader->name;
                $voucherStatesOutput[$stateToken->uuid]['mname'] = $voucherState->voucher->trader->market->name;
                $voucherStatesOutput[$stateToken->uuid]['mspon'] = $voucherState->voucher->trader->market->sponsor->name;
                $voucherStatesOutput[$stateToken->uuid]['uname'] = $voucherState->user->name;
                $voucherStatesOutput[$stateToken->uuid]['total'] = count($voucherStates);
                $voucherStatesOutput[$stateToken->uuid]['varea'] = $voucherState->voucher->sponsor->name;
                $voucherStatesOutput[$stateToken->uuid]['countarea'] = 4;

//                $voucherStatesOutput[$stateToken->uuid]['splitByArea'] = [];
//                    foreach($voucherState['splitByArea'] as $perArea){
//                        //this should split the above into voucher areas (unique()?) & sizeof?
//                        $perArea['vArea'] = 'Jam';
//                        //need this to be  count of all the vouchers with this voucher area
//                        $perArea['countArea'] = 4;
//                    }
            }
            $output = $voucherStatesOutput;

            //use group by area to create nested values?
            //update pending with the filled in voucherStates
//                $pending = $voucherStates;
            print("<pre>" . print_r($output, true) . "</pre>");
        }


        {

//                //initialise an array to use for passing the data to the index & view
//                $formattedData = [];
//
//                foreach ($output as $outputRow) {
//                    // ngl not sure why this is here as the array will be empty...
//                    // grab any existing id in the array because safety dance?
////                        $formattedData[$output->uuid] = [];
////                    $currentRow = $pendingData[$outputRow->uuid] ?? [];
//                    // map the rows from pending to the new array
//                    // overwrite with things it probably already has...
////                    if (empty($currentRow)) {
//                    $formattedData["traderName"] = $outputRow->tname;
//                    $formattedData["marketName"] = $outputRow->mname;
//                    $formattedData["area"] = $outputRow->mspon;
//                    $formattedData["requestedBy"] = $outputRow->uname;
//                    $formattedData["vouchersTotal"] = $outputRow->total;
////                        $currentRow[$output->uuid]["voucherAreas"] = [];
//                    }
//                    //I think this is to allow it to work for the dropdown
////                    $currentRow["voucherAreas"][$outputRow->vArea] = $outputRow->byVArea;
////                    $currentRow["voucherAreas"][$outputRow->countArea] = $outputRow->countVArea;
////                    // update pendingData;
////                    $pendingData[$outputRow->uuid] = $currentRow;
//                $pendingData = $formattedData;
                }
                return $pendingData;
            }

    /** Lightweight check for outstanding payments to highlight in dashboard
     * @param $idkyet
     * @return bool
     */
    public static function checkIfOutstandingPayments(): bool
    {
        $date = Carbon::now()->subDays(7)->startOfDay();

        $payments = DB::table('state_tokens')
            ->where('created_at','>',$date)
            ->whereNull('admin_user_id')
            ->count();
        if($payments > 0){
            return true;
        }
        else {
            return false;
        }
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
                }

                else {
                    Log::info('Failure Processing Payout Transition');
                    $success = false;
                    break;
                }
            }
            if ($success){
                $state_token->admin_user_id = Auth::user()->id;
                $state_token->save();
            }
        }
        return redirect()->route('admin.payments.index')->with('notification','Vouchers Paid!');
    }
}
