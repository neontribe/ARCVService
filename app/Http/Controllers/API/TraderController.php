<?php

namespace App\Http\Controllers\API;

use DB;
use App\Trader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TraderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function show(Trader $trader)
    {
        //
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

    public function showVouchers(Trader $trader)
    {
        // GET api/traders/{trader}/vouchers?status=unpaid
        // Find all vouchers that belong to {trader}
        // that have not had a GIVEN status as a voucher_state IN THIER LIVES.

        // Could be extended to incorporate ?currentstate=
        // Find all the vouchers that belong to {trader}
        // that have current voucher_state of given currentstate.

        $status = request()->input('status');

        if (empty($state)) {
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
}
