<?php

namespace App\Http\Controllers\API;

use DB;
use Auth;
use App\Trader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TraderController extends Controller
{

    /**
     * A list of traders belonging to auth's user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // We won't be here if not because of the api middleware but...
        if (!Auth::user()) { abort(401); }

        $traders = Auth::user()->traders;

        return response()->json($traders, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function show(Trader $trader)
    {
        return response()->json($trader, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function edit(Trader $trader)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Trader $trader)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function destroy(Trader $trader)
    {
        //
    }

    /**
     * Display the vouchers associated with the trader.
     * Optionally include query param 'status' to filter results by state.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function showVouchers(Trader $trader)
    {
        // GET api/traders/{trader}/vouchers?status=unpaid
        // Find all vouchers that belong to {trader}
        // that have not had a GIVEN status as a voucher_state IN THIER LIVES.

        // Could be extended to incorporate ?currentstate=
        // Find all the vouchers that belong to {trader}
        // that have current voucher_state of given currentstate.

        $status = request()->input('status');

        if (empty($status)) {
            // Get all the trader's assigned vouchers
            $vouchers = $trader->vouchers->all();
        } else {
            // Get the vouchers with given status, mapped to these states.
            switch ($status) {
                case "unpaid":
                    $stateCondition = "reimbursed";
                    break;
                default:
                    $stateCondition = null;
                    break;
            }

            $statedVoucherIDs = DB::table('vouchers')
                ->leftJoin('voucher_states', 'vouchers.id', '=', 'voucher_states.voucher_id')
                ->leftJoin('traders', 'vouchers.trader_id', '=', 'traders.id')
                ->where('voucher_states.to', $stateCondition)
                ->pluck('vouchers.id')->toArray();

            // subtract them from the collected ones
            $vouchers = $trader->vouchers->whereNotIn('id', $statedVoucherIDs);
        }

        return response()->json($vouchers, 200);
    }

    /**
     * Display the Trader's Voucher history.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function showVoucherHistory(Trader $trader)
    {
        $trader = \App\Trader::find(1);

        $vouchers = $trader->vouchersConfirmed;
        $voucher_history = [];
        foreach ($vouchers as $v) {
            // Group by the created at date on the payment_pending state.
            //$voucher_history[$v->voucher_state->created_at][] = $v;
        }
        return response()->json($voucher_history, 200);
    }



}
