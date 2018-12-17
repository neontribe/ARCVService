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
        return response($paymentUuid, 200)
            ->header('Content-Type', 'text/plain');
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
