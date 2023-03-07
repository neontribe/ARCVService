<?php

namespace App\Http\Controllers\API;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiTransitionVoucherRequest;
use App\StateToken;
use App\Trader;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class VoucherController extends Controller
{
    /**
     * Collect vouchers - this might change to a more all-purpose update vouchers.
     *
     * route POST api/vouchers
     *
     * @param ApiTransitionVoucherRequest $request
     * @return JsonResponse
     */
    public function transition(ApiTransitionVoucherRequest $request): JsonResponse
    {
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock('transition');

        if ($lock->acquire()) {
            \Log::info('I have a lock');
            $trader = Trader::findOrFail($request->input('trader_id'));

            //create unique, cleaned vouchers
            $uniqueVouchers = array_unique(
                Voucher::cleanCodes($request->input('vouchers'))
            );

            // Do we want to validate codes by regex rule before we try to find them or meh?
            $vouchers = Voucher::findByCodes($uniqueVouchers);

        // We'll be collating the codes by category.
        $responses = [
            'success_add' => [],
            'success_reject' => [],
            'own_duplicate' => [],
            'other_duplicate' => [],
            // For now - get the ones not in that list - they are bad codes.
            // We need to re-key the array here because otherwise the json response will return object for non 0 starting.
            'invalid' => array_values(
                array_diff(
                    $uniqueVouchers,
                    $vouchers->pluck('code')->toArray()
                )
            ),
            'failed_reject' => [],
            'undelivered' => [],
        ];

        $transition = $request->input('transition');

        // If 'confirm', we'll need a StateToken for Later
        $stateToken = ($transition === 'confirm')
            ? factory(StateToken::class)->create()
            : null
        ;

        // Fetch the date we start to care about deliveries
        $collect_delivery_date = Carbon::parse(config('arc.first_delivery_date'));

        /** @var Voucher $voucher */
        foreach ($vouchers as $voucher) {
            // Don't transition newer, undelivered vouchers
            if ($transition === "collect" &&
                // delivery_id is null
                $voucher->delivery_id === null &&
                // The cutoff date is less than or equal to the created_at and
                $collect_delivery_date->lessThanOrEqualTo($voucher->created_at)
            ) {
                // Don't proceed, just file this voucher for a message
                $responses['undelivered'][] = $voucher->code;
                continue;
            }

            // Work out which transition we need to roll back to for "rejects"
            if ($transition === "reject") {
                $last_state = $voucher->getPriorState();
                if ($last_state === null) {
                    $responses['failed_reject'][] = $voucher->from;
                    continue;
                }
                $transition = "reject-to-" . $last_state->from;
            }

            // Can we do a transition already?
            if (!$voucher->transitionAllowed($transition)) {
                // No; drop vouchers into a relevant bin
                if ($voucher->trader_id === $trader->id) {
                    // Trader has already submitted this voucher
                    $responses['own_duplicate'][] = $voucher->code;
                } else {
                    // Another trader has mistakenly submitted this voucher,
                    // Or the transition isn't valid (i.e expired state)
                    $responses['other_duplicate'][] = $voucher->code;
                }
                continue;
            }

            // Was the _original_ transition "reject" (now reject-to-...)
            if ($request->input('transition') === 'reject') {
                $voucher->trader_id = null;
                $responses['success_reject'][] = $voucher->code;
            } else {
                $voucher->trader_id = $trader->id;
                $responses['success_add'][] = $voucher->code;
            }

            // Transitioning also saves model changes above
            $voucher->applyTransition($transition);

            // If this is a 'confirm' transition - add to a list
            // for sending to ARC admin. This is a request for payment.
            if ($transition === 'confirm') {
                $vouchers_for_payment[] = $voucher;

                // Fetch the last transition and add the state
                $voucher->getPriorState()
                    ->stateToken()
                    ->associate($stateToken)
                    ->save();
            }
        }

            // If there are any confirmed ones... trigger the email.
            if (!empty($vouchers_for_payment)) {
                \Log::info('SENDING MAIL ' . count($vouchers_for_payment));
                $this->emailVoucherPaymentRequest($trader, $stateToken, $vouchers_for_payment);
            }
            $lock->release();
            return response()->json(
                $this->constructResponseMessage($responses)
            );
        } else {
            \Log::info('No lock for me!');
            $responses['own_duplicate'][] = '000000';
            return response()->json(
                $this->constructResponseMessage($responses)
            );
        }
    }

    /**
     * Email a Trader's Voucher Payment Request.
     * @param Trader $trader
     * @param StateToken $stateToken
     * @param $vouchers
     * @return JsonResponse
     */
    public function emailVoucherPaymentRequest(Trader $trader, StateToken $stateToken, $vouchers): JsonResponse
    {
        $title = 'A report containing voucher payment request for '
            . $trader->name . '.';
        // Request date string as dd-mm-yyyy
        $date = Carbon::now()->format('d-m-Y');
        // Todo factor excel/csv create functions out into service.
        $traderController = new TraderController();
        $file = $traderController->createVoucherListFile($trader, $vouchers, $title, $date);
        $programme_amounts = $traderController->getProgrammeAmounts($vouchers);

        event(new VoucherPaymentRequested(Auth::user(), $trader, $stateToken, $vouchers, $file, $programme_amounts));

        return response()->json(['message' => trans('api.messages.voucher_payment_requested')], 202);
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

    /**
     * Helper to construct voucher validation response messages.
     *
     * @param array $responses
     * @return array $message
     */
    private function constructResponseMessage($responses): array
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
                    $content = [
                        'message' => trans('api.messages.voucher_success_add'),
                    ];
                    break;
                case 'success_reject':
                    $content = [
                        'message' => trans('api.messages.voucher_success_reject'),
                    ];
                    break;
                case 'own_duplicate':
                    $content = [
                        'warning' => trans('api.errors.voucher_own_dupe', [
                            'code' => $responses['own_duplicate'][0],
                        ]),
                    ];
                    break;
                case 'other_duplicate':
                    $content = [
                        'warning' => trans('api.errors.voucher_other_dupe', [
                            'code' => $responses['other_duplicate'][0],
                        ]),
                    ];
                    break;
                case 'failed_reject':
                    $content = [
                        'warning' => trans('api.errors.voucher_failed_reject', [
                            'code' => $responses['failed_reject'][0],
                        ]),
                    ];
                    break;
                case 'undelivered':
                    $content = [
                        'warning' => trans('api.errors.voucher_unavailable', [
                            'code' => $responses['undelivered'][0],
                        ]),
                    ];
                    break;
                case 'invalid':
                default:
                    $content = [
                        'error' => trans('api.errors.voucher_unavailable'),
                    ];
                    break;
            }
            return $content;
        }

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
