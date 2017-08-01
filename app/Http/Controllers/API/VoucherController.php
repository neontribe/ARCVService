<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherDuplicateEntered;
use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\Http\Controllers\Controller;
use App\Trader;
use App\Voucher;
use App\User;
use Auth;
use Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
                // These are duplicates - or invalid for another reason.
                // For now - we treat them all as 'duplicates'.
                // Advise trader to send in for verification and payment.
                if ($trader->id === $voucher->trader_id) {
                    // Trader has already submitted this voucher
                    $own_duplicate_codes[] = $voucher->code;
                } else {
                    // Another trader has mistakenly submitted this voucher,
                    // Or the tranision isn't valid (i.e expired state)
                    $other_duplicate_codes[] = $voucher->code;
                }
            }
        }

        // If there are any confirmed ones... trigger the email.
        if (sizeof($vouchers_for_payment) > 0) {
            // Todo We need to change something...
            // If the email fails for some reason,
            // the User will not be aware.
            // The Vouchers will be transitioned but the ARC admin won't know.
            $this->emailVoucherPaymentRequest($trader, $vouchers_for_payment);
        }


        $trader_name = $trader->name;
        if(count($own_duplicate_codes) > 0) {
            Log::info(
                "[xXx] A User attempted to log these duplicate vouchers already claimed by $trader_name:"
                . print_r($own_duplicate_codes, TRUE)
            );
        }
        if(count($other_duplicate_codes) > 0) {
            Log::info(
                "[xXx] A User attempted to log these duplicate vouchers claimed by other traders:"
                . print_r($other_duplicate_codes, TRUE)
            );

            event(new VoucherDuplicateEntered(Auth::user(), $trader, $other_duplicate_codes));
        }

        // We might want to annotate somehow with the type of transition here.
        // Currently we can
        //  'collect' - submit and
        //  'confirm' - request payment on.
        $responses['success'] = $success_codes;
        $responses['own_duplicate'] = $own_duplicate_codes;
        $responses['other_duplicate'] = $other_duplicate_codes;
        $responses['invalid'] = $bad_codes;

        $response = $this->constructResponseMessage($responses);

        return response()->json($response, 200);
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

    /**
     * Helper to construct voucher validation response messages.
     *
     * @param Array $response
     * @return array $message
     */
    private function constructResponseMessage($responses)
    {
        // If there is only one voucher code being checked.
        $total_submitted = 0;
        $error_type = '';
        foreach ($responses as $key => $code) {
            $total_submitted += count($code);

            if (count($code) === 1) {
                // We will only use this if there is a total of 1 voucher submitted.
                // So no problem if 2 sets have 1 voucher in them. It is ignored.
                $error_type = $key;
            }
        }
        if ($total_submitted === 1) {
            switch ($error_type) {
                case 'success':
                    return [
                        'message' => trans('api.messages.voucher_success')
                    ];
                    break;
                case 'own_duplicate':
                    return [
                        'warning' => trans('api.errors.voucher_own_dupe', [
                            'code' => $responses['own_duplicate'][0]
                        ])
                    ];
                    break;
                case 'other_duplicate':
                    return [
                        'warning' => trans('api.errors.voucher_other_dupe', [
                            'code' => $responses['other_duplicate'][0]
                        ])
                    ];
                    break;
                case 'invalid':
                default:
                    return [
                        'error' => trans('api.errors.voucher_invalid')
                    ];
                    break;
            }
        } else {
            $message_text = trans('api.messages.batch_voucher_submit', [
                'success_amount' => count($responses['success']),
                'duplicate_amount' => count($responses['own_duplicate'])
                    + count($responses['other_duplicate']),
                'invalid_amount' => count($responses['invalid'])
            ]);

            if(count($responses['other_duplicate']) > 0) {
                $message_text .= ' ' . trans(
                    'api.messages.batch_voucher_submit_duplicates',
                    [
                        'other_dupes' => implode(', ', $responses['other_duplicate']),
                    ]
                );
            }

            return ['message' => $message_text];
        }
    }
}
