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

        // create unique, cleaned voucher codes from the request
        $submittedCodes = array_unique(Voucher::cleanCodes($request->input('vouchers')));

        $query = Voucher::whereIn('code', $submittedCodes);
        $foundCodes = $query->pluck('code')->all();
        $invalidCodes = array_values(array_diff($submittedCodes, $foundCodes));

        $processor = new TransitionProcessor($trader, $request->input('transition'));

        $processor->handle($query);

        // Inject the invalid codes detected above into the processor's public
        // responses array so constructResponseMessage() counts them correctly,
        // matching the output the original code would have produced.
        $processor->responses['invalid'] = $invalidCodes;

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
