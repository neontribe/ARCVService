<?php

namespace App\Http\Controllers\API;

use Illuminate\Pagination\Paginator;
use Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Log;
use App\Trader;
use App\Voucher;
use App\User;

class VoucherController extends Controller
{

    protected $user;

/*
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('api')->user();
            return $next($request);
        });
    }
 */

    /**
     * Collect vouchers - this might change to a more all-purpose update vouchers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function collect(Request $request)
    {
        // TODO: Generalise and reroute
        // for the prupsoes of this iteration, store() will progress
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

        // Get out - no vuochers to process.
        if (!$request['vouchers'] || $request['vouchers'] < 1) {
            return response("no voucher data", 400);
        }

        // Once we have implemented login...
        $user = Auth::user();
        // Until then...
        if (!$user = User::find($request['user_id'])) {
            $user = User::find(1); // just be the first person for now.
        }
        // We need auth'd user to perform voucher state changes.
        Auth::login($user);

        $uniqueVouchers = array_unique($request['vouchers']);

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

        $messages['success'] = 'Sucessfully collected ';
        $messages['failures'] .= 'Failed to collect ';
        foreach ($fail_codes as $code) {
            $messages['failures'] .= $code;
        }
        dd($messages);
        return response()->json($messages, 200);
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
