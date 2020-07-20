<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Voucher;
use Illuminate\Http\Response;

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
     * @param  Voucher $voucher
     * @return Response
     */
    public function show(Voucher $voucher)
    {
        return $voucher;
    }
}
