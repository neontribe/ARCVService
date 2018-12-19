<?php

namespace App\Http\Controllers\Store;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:store');
    }

    /** Get a specific payment request by link
     * @param $paymentUuid
     * @return mixed
     */
    public function show($paymentUuid)
    {

        $trader = "Mike's Excellent Courgettes";
        $voucher_codes = [ 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003', 'RVNT001', 'RVNT002', 'RVNT003' ];

        return view('store.payment_request', [
            'trader' => $trader,
            'voucher_codes' => $voucher_codes,
        ]);
    }

    /** Pay a specific payment request by link
     * @param $paymentUuid
     * @return mixed
     */
    public function update($paymentUuid)
    {
        return response($paymentUuid, 200)
            ->header('Content-Type', 'text/plain');
    }
}
