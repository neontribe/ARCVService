<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTransitionVoucherRequest;
use App\Services\TransitionProcessor\TransitionProcessor;
use App\Trader;
use App\Voucher;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    /**
     * Legacy transition route for older clients.
     * route POST api/vouchers
     */
    public function legacyTransition(ApiTransitionVoucherRequest $request): JsonResponse
    {
        $trader = Trader::findOrFail($request->input('trader_id'));

        $submittedCodes = array_unique(Voucher::cleanCodes($request->input('vouchers')));

        $query = Voucher::whereIn('code', $submittedCodes);
        $foundCodes = $query->pluck('code')->all();
        $invalidCodes = array_values(array_diff($submittedCodes, $foundCodes));

        $processor = new TransitionProcessor($trader, $request->input('transition'));

        $response = $processor->handle($query);
        $response->addInvalid($invalidCodes);

        return response()->json($response->constructResponseMessage());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $code): JsonResponse
    {
        return response()->json(Voucher::findByCode($code));
    }
}
