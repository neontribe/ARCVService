<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Log;
use App\Trader;
use App\Voucher;
use App\User;

class VoucherController extends Controller
{
    /**
     * Collect vouchers - this might change to a more all-purpose update vouchers.
     *
     * route POST api/vouchers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function transition(Request $request)
    {
        /* expecting a body of type application/josn; a collect transition looks like
        {
            "trader_id" : 1,
            "transition" : "collect"
            "vouchers" : [
                "SOL00000001",
                "SOL00000002",
                "SOL00000002",
            ]
        }
        */

        // Get out - no transition specified.
        if (!$request['transition']) {
            return response("No transition", 400);
        }

        // Get out - no vouchers to process.
        if (!$request['vouchers'] || $request['vouchers'] < 1) {
            return response("No voucher data", 400);
        }

        // Get out - no trader declared.
        if (!$request['trader_id']) {
            return response("No trader id", 400);
        }

        // Make sure we have a valid trader.
        if (!$trader = Trader::find($request['trader_id'])) {
            return response("Trader not found", 400);
        }

        $uniqueVouchers = array_unique($request['vouchers']);

        // Do we want to validate codes by regex rule before we try to find them or meh?

        // What response do we get for invalids here?
        // Might be better to fetch in turn so we have response for each.
        $vouchers = Voucher::findByCodes($uniqueVouchers);

        // For now - get the ones not in that list - they are bad codes.
        // We need to rekey the array here because otherwise the json reponse will return object for non 0 starting.
        $bad_codes = array_values(array_diff(
            $uniqueVouchers,
            $vouchers->pluck('code')->toArray()
        ));

        // For the pre-alpha we 'collect'.
        $transition = $request['transition'];
        $success_codes = [];
        $fail_codes = [];
        foreach ($vouchers as $voucher) {
            // can we?
            if ($voucher->transitionAllowed($transition)) {
                // yes! do the thing!
                $voucher->trader_id = $trader->id;

                // this saves the model too.
                $voucher->applyTransition($transition);
                // Success for this one.
                $success_codes[] = $voucher->code;
            } else {
                // no! add it to a list of failures.
                // Fail for this one.
                $fail_codes[] = $voucher->code;
            }
        }

        $responses['success'] = $success_codes;
        $responses['fail'] = $fail_codes;
        $responses['invalid'] = $bad_codes;
        return response()->json($responses, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $code
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        return response()->json(Voucher::findByCode($code));
    }
}
