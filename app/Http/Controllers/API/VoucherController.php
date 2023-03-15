<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTransitionVoucherRequest;
use App\Services\TransitionProcessor;
use App\Trader;
use App\Voucher;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    /**
     * Legacy transition route for older clients
     * route POST api/vouchers
     *
     * @param ApiTransitionVoucherRequest $request
     * @return JsonResponse
     */
    public function legacyTransition(ApiTransitionVoucherRequest $request): JsonResponse
    {
        // get our trader
        $trader = Trader::findOrFail($request->input('trader_id'));

        //create unique, cleaned vouchers
        $voucherCodes = array_unique(Voucher::cleanCodes($request->input('vouchers')));

        $processor = new TransitionProcessor($trader, $request->input('transition'));

        $processor->handle($voucherCodes);

        return response()->json($processor->constructResponseMessage());
    }

    /**
     * Display the specified resource.
     *
     * @param string $code
     * @return JsonResponse
     */
    public function show(string $code): JsonResponse
    {
        return response()->json(Voucher::findByCode($code));
    }
}
