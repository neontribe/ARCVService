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
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class VoucherController extends Controller
{
    // We'll be collating the codes by category.
    private array $responses = [];

    private array $vouchers_for_payment = [];

    private Trader $trader;

    private Collection $vouchers;

    private string $transition;

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
        $this->responses = [
            'success_add' => [],
            'success_reject' => [],
            'own_duplicate' => [],
            'other_duplicate' => [],
            'invalid' => [],
            'failed_reject' => [],
            'undelivered' => [],
        ];

        // get our trader
        $this->trader = Trader::findOrFail($request->input('trader_id'));

        //create unique, cleaned vouchers
        $uniqueVouchers = array_unique(Voucher::cleanCodes($request->input('vouchers')));

        // set a lock, to prevent double submits
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock('transition');

        if ($lock->acquire()) {
            // get the vouchers
            $this->vouchers = Voucher::findByCodes($uniqueVouchers);

            // For now - get the ones not in that list - they are bad codes.
            // We need to re-key the array here because otherwise the json response will return object for non 0 starting.
            $this->responses['invalid'] = array_values(
                array_diff(
                    $uniqueVouchers,
                    $this->vouchers->pluck('code')->toArray()
                )
            );

            $this->transition = $request->input('transition');

            switch ($this->transition) {
                case 'collect' :
                    $this->handleCollect();
                    break;
                case 'confirm':
                    $this->handleConfirm();
                    break;
                case 'reject':
                    $this->handleReject();
                    break;
                default:
                    $this->handleDefault();
            }

            $lock->release();
        } else {
            Log::info('No lock for me!');
            $this->responses['own_duplicate'][] = '000000';
        }

        if (!empty($this->vouchers_for_payment)) {
            // If there are any confirmed ones respond appropriately.
            return response()->json(['message' => trans('api.messages.voucher_payment_requested')]);
        }
        return response()->json(self::constructResponseMessage($this->responses));
    }

    /**
     * @return void
     */
    public function handleCollect(): void
    {
        // Fetch the date we start to care about deliveries
        $collect_delivery_date = Carbon::parse(config('arc.first_delivery_date'));
        $transition = $this->transition;

        foreach ($this->vouchers as $voucher) {
            // Don't transition newer, undelivered vouchers
            if (// delivery_id is null
                $voucher->delivery_id === null &&
                // The cut-off date is less than or equal to the created_at
                $collect_delivery_date->lessThanOrEqualTo($voucher->created_at)
            ) {
                // Don't proceed, just file this voucher for a message
                $this->responses['undelivered'][] = $voucher->code;
                continue;
            }

            if ($this->doTransition($voucher, $transition)) {
                $this->responses['success_add'][] = $voucher->code;
            };
        }
    }

    /**
     * @return void
     */
    public function handleConfirm() : void
    {
        // If 'confirm', we'll need a StateToken for Later
        $stateToken = factory(StateToken::class)->create();
        $transition = $this->transition;

        foreach ($this->vouchers as $voucher) {

            $voucher->trader_id = $this->trader->id;

            // Can we do a transition already?
            if ($this->doTransition($voucher, $transition)) {
                // add to a list for sending to ARC admin. This is a request for payment.
                $this->vouchers_for_payment[] = $voucher;

                // Fetch the last transition and add the state
                $voucher->getPriorState()->stateToken()->associate($stateToken)->save();
            }
        }
        // If there are any confirmed ones... trigger the email.
        if (!empty($this->vouchers_for_payment)) {
            Log::info('SENDING MAIL ' . count($this->vouchers_for_payment));
            self::emailVoucherPaymentRequest($this->trader, $stateToken, $this->vouchers_for_payment);
        }
    }

    /**
     * @return void
     */
    public function handleReject(): void
    {
        foreach ($this->vouchers as $voucher) {
            // Work out which transition we need to roll back to for "rejects"
            $last_state = $voucher->getPriorState();
            if ($last_state === null) {
                $this->responses['failed_reject'][] = $voucher->from;
                continue;
            }

            // alter the transition
            $transition = "reject-to-" . $last_state->from;

            // will be saved if transition success
            $voucher->trader_id = null;

            // Can we do a transition already?
            if ($this->doTransition($voucher, $transition)) {
                $this->responses['success_reject'][] = $voucher->code;
            }
        }
    }

    /**
     * @return void
     */
    public function handleDefault(): void
    {
        $transition = $this->transition;
        foreach ($this->vouchers as $voucher) {
            // Can we do a transition already?
            $this->doTransition($voucher, $transition);
        }
    }

    /**
     * @param Voucher $voucher
     * @param string $transition
     * @return bool
     */
    private function doTransition(Voucher $voucher, string $transition): bool
    {
        // Can we do a transition already?
        try {
            $voucher->applyTransition($transition);
        } catch (Exception $e) {
            Log::warning($e->getMessage());
            if ($voucher->trader_id === $this->trader->id) {
                // Trader has already submitted this voucher
                $this->responses['own_duplicate'][] = $voucher->code;
            } else {
                // Another trader has mistakenly submitted this voucher,
                // Or the transition isn't valid (i.e. expired state)
                $this->responses['other_duplicate'][] = $voucher->code;
            }
            return false;
        }
        return true;
    }

    /**
     * Helper to construct voucher validation response messages.
     *
     * @param array $responses
     * @return array $message
     */
    private static function constructResponseMessage(array $responses): array
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
            return match ($error_type) {
                'success_add' => [
                    'message' => trans('api.messages.voucher_success_add'),
                ],
                'success_reject' => [
                    'message' => trans('api.messages.voucher_success_reject'),
                ],
                'own_duplicate' => [
                    'warning' => trans('api.errors.voucher_own_dupe', [
                        'code' => $responses['own_duplicate'][0],
                    ]),
                ],
                'other_duplicate' => [
                    'warning' => trans('api.errors.voucher_other_dupe', [
                        'code' => $responses['other_duplicate'][0],
                    ]),
                ],
                'failed_reject' => [
                    'warning' => trans('api.errors.voucher_failed_reject', [
                        'code' => $responses['failed_reject'][0],
                    ]),
                ],
                'undelivered' => [
                    'warning' => trans('api.errors.voucher_unavailable', [
                        'code' => $responses['undelivered'][0],
                    ]),
                ],
                default => [
                    'error' => trans('api.errors.voucher_unavailable'),
                ],
            };
        }

        return [
            // Todo: This message needs work - but not this round.
            'message' => trans('api.messages.batch_voucher_submit', [
                'success_amount' => count($responses['success_add']),
                'duplicate_amount' => count($responses['own_duplicate']) + count($responses['other_duplicate']),
                'invalid_amount' => count($responses['invalid']) + count($responses['undelivered']),
            ]),
        ];
    }

    /**
     * Email a Trader's Voucher Payment Request.
     * @param Trader $trader
     * @param StateToken $stateToken
     * @param $vouchers
     * @return void
     */
    public static function emailVoucherPaymentRequest(Trader $trader, StateToken $stateToken, $vouchers): void
    {
        $title = "A report containing voucher payment request for $trader->name.";
        // Request date string as dd-mm-yyyy
        $date = Carbon::now()->format('d-m-Y');
        // Todo factor excel/csv create functions out into service.
        $traderController = new TraderController();
        $file = $traderController->createVoucherListFile($trader, $vouchers, $title, $date);
        $programme_amounts = $traderController->getProgrammeAmounts($vouchers);

        event(new VoucherPaymentRequested(Auth::user(), $trader, $stateToken, $vouchers, $file, $programme_amounts));
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
