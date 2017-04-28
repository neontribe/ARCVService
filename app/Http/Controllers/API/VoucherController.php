<?php

namespace App\Http\Controllers\API;

use Auth;
use Response;
use App\Voucher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VoucherController extends Controller
{

    protected $user;

/*
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('api')->user();
            return $next($request);
        });
    }
*/
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $vouchers = Voucher::all();
        return response()->json($vouchers->toArray());
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
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function show(Voucher $voucher)
    {
        return response()->json(Voucher::findByCode($voucher));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function edit(Voucher $voucher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Voucher $voucher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Voucher  $voucher
     * @return \Illuminate\Http\Response
     */
    public function destroy(Voucher $voucher)
    {
        //
    }

    /**
     * show the transitions that a voucher has gone through
     *
     * @param \App\Voucher $voucher
     * @return \Illuminate\Http\Response
     */
    public function showTransitions(Voucher $voucher)
    {
        //
    }

    /**
     * Redeem a bunch of vouchers at once.
     * Look for a complex data structure in the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function batchRedeem(Request $request)
    {
        //
    }

    /**
     * Redeem a single voucher.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function redeem(Request $request, Voucher $voucher)
    {

    }
}
