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
     * Progress vouchers - this is a more all-purpose update vouchers function.
     *
     * route POST api/vouchers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function progress(Request $request)
    {

        $transitionSet = [];

        // Expecting postvars to contain state change arrays of vouchers beginning "transition_".
        $rxTransition = '/^transition_([\w\d_.-]+)$/';

        // Assume the worst, no state data.
        $noData = true;

        foreach ($request as $key => $value) {
            // Check the keys.
            if (preg_match($rxTransition, $key, $transMatches)) {
                if (count($value)) {
                    // TransMatches[1] should contain the transaction name captured by regex.
                    $transitionSet[$transMatches[1]] = $value;
                    // Found some data!
                    $noData = false;
                }
            }
        }
        // Get out - no state changes to process.
        if ($noData) {
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

        $success_codes = [];
        $fail_codes = [];
        $bad_codes = [];

        foreach ($transitionSet as $transition => $codes) {
            $unique_codes = array_unique($codes);
            // Do we want to validate codes by regex rule before we try to find them or meh?
            // What response do we get for invalids here?
            // Might be better to fetch in turn so we have response for each.

            $vouchers = Voucher::findByCodes($unique_codes);

            // For now - get the ones not in that list - they are bad codes.
            array_merge($bad_codes, array_diff(
                $unique_codes,
                $vouchers->pluck('code')->toArray()
            ));

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
        }
        /* This should probably be reconsidered.
        // I think duplicates could slip in IF
        // - multiple transitions are submitted at once AND
        // - a code appears in both
        */
        $responses['success'] = $success_codes;
        $responses['fail'] = $fail_codes;
        $responses['invalid'] = array_unique($bad_codes);
        return response()->json($responses, 200);
    }

    /**
     * Collect vouchers - this might change to a more all-purpose update vouchers.
     *
     * route POST api/vouchers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function collect(Request $request)
    {
        // TODO: Generalise and reroute
        // for the prupsoes of this iteration, collect() will progress
        // "allocated" to "recorded";
        // This would better be generalised as an "update" that progresses
        // to the given state for each voucher.
        /* expecting a body of type application/josn
        {
            "user_id" : 1,
            "trader_id" : 1,
            "vouchers" : [
                "SOL00000001",
                "SOL00000002",
                "SOL00000002",
            ]
        }
        */

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
        $bad_codes = array_diff(
            $uniqueVouchers,
            $vouchers->pluck('code')->toArray()
        );

        // For the pre-alpha we 'collect'.
        $transition = 'collect';
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
