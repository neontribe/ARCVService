<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Voucher;
use Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Http\Request;
use Log;
use Response;

class VouchersController extends Controller
{

    /**
     * Display a listing of Vouchers.
     *
     * @return json
     */
    public function index()
    {
        $vouchers = Voucher::all();
        return view('service.vouchers.index', compact('vouchers'));
    }


    /**
     * Show the form for creating new Vouchers.
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
    public function store(Voucher $voucher)
    {
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
     * Display the specified Voucher.
     *
     * @param  App\Voucher $voucher
     * @return \Illuminate\Http\Response
     */
    public function show(Voucher $voucher)
    {
        return $voucher;
    }
}
