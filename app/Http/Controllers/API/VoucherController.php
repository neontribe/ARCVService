<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\Controller;
use App\StateToken;
use App\Trader;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VoucherController extends Controller
{
    /**
     * Collect vouchers - this might change to a more all-purpose update vouchers.
     *
     * route POST api/vouchers
     *
     * @param Request $request
     * @return ResponseFactory|Application|JsonResponse|\Symfony\Component\HttpFoundation\Response
     */

    public function transition(Request $request)
    {
        /* expecting a body of type application/json; a collect transition looks like
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

        // TODO : Tidy these checks into a FormRequest for API requests.
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

        //cleanup the codes
        $cleanedVouchers = Voucher::cleanCodes($request['vouchers']);

        //create unique vouchers
        $uniqueVouchers = array_unique($cleanedVouchers);

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
        $reject_codes = [];
        $other_duplicate_codes = [];
        $own_duplicate_codes = [];
        $failed_rejects = [];
        $vouchers_for_payment = [];
        $undelivered_codes = [];

        // Fetch the date we start to care about deliveries
        $collect_delivery_date = Carbon::parse(config('arc.collect_delivery_date'));


        if ($transition === 'confirm') {
            // We'll need a StateToken for Later
            $stateToken = factory(StateToken::class)->create();
        }

        /** @var Voucher $voucher */
        // TODO: Unsquirrel this ginormo-function.
        foreach ($vouchers as $voucher) {
            // Don't transition newer, undelivered vouchers
            if ($transition === "collect" &&
                // The cutoff date is less than or equal to the create at and
                $collect_delivery_date->lessThanOrEqualTo($voucher->created_at) &&
                // delivery_id is null
                $voucher->delivery_id === null
            ) {
                // Dont proceed, just file this voucher for a message
                $undelivered_codes[] = $voucher->code;
            } else {
                // Work out which transition we need to roll back to for "rejects"
                if ($transition === "reject") {
                    $last_state = $voucher->getPriorState();
                    if (!$last_state) {
                        $failed_rejects[] = $voucher->from;
                        continue;
                    }
                    $transition = "reject-to-" . $last_state->from;
                }

                // can we do a transition already?
                if ($voucher->transitionAllowed($transition)) {
                    // Was the _original_ transition "reject" (now reject-to-...)
                    if ($request['transition'] === 'reject') {
                        $voucher->trader_id = null;
                        $reject_codes[] = $voucher->code;
                    } else {
                        $voucher->trader_id = $trader->id;
                        $success_codes[] = $voucher->code;
                    }

                    // This saves the model too.
                    $voucher->applyTransition($transition);

                    // If this is a confirm transition - add to a list
                    // for sending to ARC admin. This is a request for payment.
                    if ($transition === 'confirm' && $stateToken) {
                        $vouchers_for_payment[] = $voucher;

                        // Fetch the last transition and add the state
                        $voucher->getPriorState()
                            ->stateToken()
                            ->associate($stateToken)
                            ->save();
                    }
                } else {
                    // These are duplicates - or invalid for another reason.
                    // For now - we treat them all as 'duplicates'.
                    // Advise trader to send in for verification and payment.
                    ($voucher->trader_id === $trader->id)
                        // Trader has already submitted this voucher
                        ? $own_duplicate_codes[] = $voucher->code
                        // Another trader has mistakenly submitted this voucher,
                        // Or the transition isn't valid (i.e expired state)
                        : $other_duplicate_codes[] = $voucher->code;
                }
            }
        }

        // If there are any confirmed ones... trigger the email.
        if (sizeof($vouchers_for_payment) > 0) {
            // This email*could* fail; However, ARC admin will eventually be able to see a list of voucher states.
            $this->emailVoucherPaymentRequest($trader, $stateToken, $vouchers_for_payment);
        }

        // Collate the codes by category.
        $responses['success_add'] = $success_codes;
        $responses['success_reject'] = $reject_codes;
        $responses['own_duplicate'] = $own_duplicate_codes;
        $responses['other_duplicate'] = $other_duplicate_codes;
        $responses['invalid'] = $bad_codes;
        $responses['failed_reject'] = $failed_rejects;
        $responses['undelivered'] = $undelivered_codes;

        $response = $this->constructResponseMessage($responses);

        return response()->json($response, 200);
    }

    /**
     * Email a Trader's Voucher Payment Request.
     *
     * @param Trader $trader
     * @param StateToken $stateToken
     * @param Collection Voucher $vouchers
     * @return Response
     */
    public function emailVoucherPaymentRequest(Trader $trader, StateToken $stateToken, $vouchers)
    {
        $title = 'A report containing voucher payment request for '
            . $trader->name . '.';
        // Request date string as dd-mm-yyyy
        $date = Carbon::now()->format('d-m-Y');
        // Todo factor excel/csv create functions out into service.
        $traderController = new TraderController();
        $file = $traderController->createVoucherListFile($trader, $vouchers, $title, $date);
        event(new VoucherPaymentRequested(Auth::user(), $trader, $stateToken, $vouchers, $file));

        // Todo not sure this is being delivered to the frontend.
        return response()->json(['message' => trans('api.messages.voucher_payment_requested')], 202);
    }

    /**
     * Display the specified resource.
     *
     * @param string $code
     * @return Response
     */
    public function show($code)
    {
        return response()->json(Voucher::findByCode($code));
    }

    /**
     * Helper to construct voucher validation response messages.
     *
     * @param array $responses
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
                case 'success_add':
                    return [
                        'message' => trans('api.messages.voucher_success_add'),
                    ];
                    break;
                case 'success_reject':
                    return [
                        'message' => trans('api.messages.voucher_success_reject'),
                    ];
                case 'own_duplicate':
                    return [
                        'warning' => trans('api.errors.voucher_own_dupe', [
                            'code' => $responses['own_duplicate'][0],
                        ]),
                    ];
                    break;
                case 'other_duplicate':
                    return [
                        'warning' => trans('api.errors.voucher_other_dupe', [
                            'code' => $responses['other_duplicate'][0],
                        ]),
                    ];
                    break;
                case 'failed_reject':
                    return [
                        'warning' => trans('api.errors.voucher_failed_reject', [
                            'code' => $responses['failed_reject'][0],
                        ]),
                    ];
                    break;
                case 'undelivered':
                    return [
                        'warning' => trans('api.errors.voucher_unavailable', [
                            'code' => $responses['undelivered'][0],
                        ]),
                    ];
                case 'invalid':
                default:
                    return [
                        'error' => trans('api.errors.voucher_unavailable'),
                    ];
                    break;
            }
        } else {
            return [
                // Todo: This message needs work - but not this round.
                'message' => trans(
                    'api.messages.batch_voucher_submit',
                    [
                        'success_amount' => count($responses['success_add']),
                        'duplicate_amount' => count($responses['own_duplicate'])
                            + count($responses['other_duplicate']),
                        'invalid_amount' => count($responses['invalid'])
                            + count($responses['undelivered']),
                    ]
                ),
            ];
        }
    }
}
