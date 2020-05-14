<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Voucher;

class VoucherController extends Controller
{
    /**
     * Display a listing of Vouchers.
     *
     * @return json
     */
    public function index()
    {
        $vouchers = Voucher::all();
        return json_decode($vouchers);
    }

    /**
     * Display the specified Voucher.
     * Todo this will change
     *
     * @param  App\Voucher $voucher
     * @return \Illuminate\Http\Response
     */
    public function show(Voucher $voucher)
    {
        return $voucher;
    }
}
