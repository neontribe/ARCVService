<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\Http\Controllers\Controller;
use App\Trader;
use App\Voucher;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

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

        $transition = $request['transition'];
        $success_codes = [];
        $other_duplicate_codes = [];
        $own_duplicate_codes = [];
        $vouchers_for_payment = [];
        foreach ($vouchers as $voucher) {
            // can we?
            if ($voucher->transitionAllowed($transition)) {
                // yes! do the thing!
                $voucher->trader_id = $trader->id;

                // this saves the model too.
                $voucher->applyTransition($transition);
                // Success for this one.
                $success_codes[] = $voucher->code;
                // If this is a confirm transition - add to a list
                // for sending to ARC admin. This is a request for payment.
                if ($transition === 'confirm') {
                    $vouchers_for_payment[] = $voucher;
                }
            } else {
                // no! add it to a list of failures.
                if {
                  // Trader has already submitted this voucher
                  $own_duplicate_codes[] = $voucher->code;
                } else {
                  // Another trader has mistakenly submitted this voucher
                  $other_duplicate_codes[] = $voucher_code;
                }
            }
        }

        // If there are any confirmed ones... trigger the email
        // event paymentRequestedEmail.
        if (sizeof($vouchers_for_payment) > 0) {
            // Todo If the email fails for some reason, the User will not be aware.
            // The Vouchers will be transitioned but the ARC admin will not know.
            $this->emailVoucherPaymentRequest($trader, $vouchers_for_payment);
        }

        // We might want to annotate somehow with the type of transition here.
        // Currently we can 'collect' - submit and 'confirm' - request payment on.
        $responses['success'] = $success_codes;
        $responses['own_duplicate'] = $own_duplicate_codes;
        $responses['other_duplicate'] = $other_duplicate_codes;
        $responses['invalid'] = $bad_codes;
        return response()->json($responses, 200);
    }

    /**
     * Email a Trader's Voucher Payment Request.
     *
     * @param Trader $trader
     * @param Collection Voucher $vouchers
     * @return \Illuminate\Http\Response
     */
    public function emailVoucherPaymentRequest(Trader $trader, $vouchers)
    {
        $title = 'A report containing voucher payment request for '
            . $trader->name . '.'
        ;
        // Request date string as dd-mm-yyyy
        $date = Carbon::now()->format('d-m-Y');
        // Todo factor excel/csv create functions out into service.
        $traderController = new TraderController();
        $file = $traderController->createVoucherListFile($trader, $vouchers, $title, $date);
        event(new VoucherPaymentRequested(Auth::user(), $trader, $vouchers, $file));

        // Todo not sure this is being delivered to the frontend.
        return response()->json(['message' => trans('api.messages.voucher_payment_requested')], 202);
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
