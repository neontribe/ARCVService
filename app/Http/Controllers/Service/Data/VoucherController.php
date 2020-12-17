<?php

namespace App\Http\Controllers\Service\Data;

use App\Http\Controllers\Controller;
use App\Voucher;

class VoucherController extends Controller
{
    /**
     * Display a listing of Vouchers.
     *
     * @return mixed
     */
    public function index()
    {
        $vouchers = Voucher::all();
        return json_decode($vouchers);
    }

    /**
     * Display the specified Voucher.
     *
     * @param Voucher $voucher
     * @return Voucher
     */
    public function show(Voucher $voucher)
    {
        return $voucher;
    }
}
