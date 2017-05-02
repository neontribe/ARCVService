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
        // GET api/traders/{trader}/vouchers?q=unpaid
        // find all vouchers that belong to Traders that have not had a GIVEN state of IN THIER LIVES

        $state = request()->input('state');

        if (empty($state)) {
            // in state string : get all the traders assigned vouchers
            $vouchers = $trader->vouchers->all();
        } else {
            $stateCondition = null;

            switch ($state) {
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
                ->where('voucher_states.to', $condition)
                ->pluck('vouchers.id')->toArray();

            // subtract them from the collected ones
            $vouchers = $trader->vouchers->whereNotIn('id', $statedVoucherIDs);
        }

        return response()->json($vouchers, 200);
    }
}
