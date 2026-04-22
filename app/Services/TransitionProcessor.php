<?php

namespace App\Services;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\StateToken;
use App\Trader;
use App\Voucher;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class TransitionProcessor
{
    public array $responses = [
        'success_add' => [],
        'success_reject' => [],
        'own_duplicate' => [],
        'other_duplicate' => [],
        'failed_reject' => [],
        'undelivered' => [],
    ];

    public array $vouchersForPayment = [];

    private Carbon $collectDeliveryDate;

    public function __construct(
        private readonly Trader $trader,
        private readonly string $transition,
        private readonly int $chunkSize = 500,
        private readonly bool $sendPaymentEmail = true
    ) {
        $this->collectDeliveryDate = Carbon::parse(config('arc.first_delivery_date'));
    }

    /**
     * Accepts a query builder describing the vouchers to transition.
     */
    public function handle(Builder $query): array
    {
        $lock = (new LockFactory(new SemaphoreStore()))->createLock('transition');

        Log::debug(sprintf(
            'Acquiring lock for transition [%s] on trader %d, %d vouchers',
            $this->transition,
            $this->trader->id,
            $query->count()
        ));

        if (!$lock->acquire()) {
            Log::info(sprintf(
                'Unable to acquire lock in TransitionProcessor for trader %d doing %s',
                $this->trader->id,
                $this->transition
            ));
            $this->responses['own_duplicate'][] = '000000';
            return $this->responses;
        }

        try {
            $this->processInChunks($query);
        } finally {
            $lock->release();
        }

        if (!empty($this->vouchersForPayment) && $this->sendPaymentEmail) {
            $vouchersForEmail = Voucher::findMany($this->vouchersForPayment)->all();
            Log::info('SENDING MAIL ' . count($vouchersForEmail));
            self::emailVoucherPaymentRequest($this->trader, $vouchersForEmail);
        }

        return $this->responses;
    }

    /**
     * Opens a database cursor over the query and processes one voucher at a time.
     */
    private function processInChunks(Builder $query): void
    {
        $stateToken = $this->transition === 'confirm' ? $this->initStateToken() : null;

        foreach ($query->lazy($this->chunkSize) as $voucher) {
            match ($this->transition) {
                'collect' => $this->handleCollect($voucher),
                'confirm' => $this->handleConfirm($voucher, $stateToken),
                'reject' => $this->handleReject($voucher),
                default => $this->handleDefault($voucher),
            };
        }
    }

    /**
     * Creates a StateToken and associates the authenticated user if present.
     */
    private function initStateToken(): StateToken
    {
        $stateToken = factory(StateToken::class)->create();
        if (Auth::check()) {
            $stateToken->user_id = Auth::id();
            $stateToken->save();
        }
        return $stateToken;
    }

    /**
     * Attempts to apply a transition to a voucher, routing failures into the
     * appropriate response bucket. Returns true only when the transition was applied.
     */
    private function doTransition(
        Voucher $voucher,
        ?int $againstTraderId = null,
        ?string $transition = null
    ): bool
    {
        $transition = $transition ?? $this->transition;
        try {
            if ($voucher->transitionAllowed($transition)) {
                $voucher->trader_id = $againstTraderId;
                $voucher->applyTransition($transition);
                Log::debug(sprintf(
                    'Transition %s on %s for trader %d',
                    $transition,
                    $voucher,
                    $againstTraderId
                ));
            } else {
                if ($voucher->trader_id === $againstTraderId) {
                    // This trader has already submitted this voucher.
                    $this->responses['own_duplicate'][] = $voucher->code;
                    Log::debug(sprintf(
                        'Transition denied %s on %s for trader %d: own_duplicate',
                        $transition,
                        $voucher,
                        $againstTraderId
                    ));
                } else {
                    // Another trader submitted this voucher, or the state is invalid.
                    $this->responses['other_duplicate'][] = $voucher->code;
                    Log::debug(sprintf(
                        'Transition denied %s on %s for trader %d: other_duplicate',
                        $transition,
                        $voucher,
                        $againstTraderId
                    ));
                }
                return false;
            }
        } catch (Exception $e) {
            // Catches impossible transition strings or unexpected model errors.
            Log::warning($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Collects a voucher, skipping undelivered ones introduced after the
     * first delivery date.
     */
    public function handleCollect(Voucher $voucher): void
    {
        Log::debug('handleCollect on voucher ' . $voucher->code);

        if (
            $voucher->delivery_id === null &&
            $this->collectDeliveryDate->lessThanOrEqualTo($voucher->created_at)
        ) {
            $this->responses['undelivered'][] = $voucher->code;
            Log::debug('Undelivered voucher ' . $voucher->code);
            return;
        }

        if ($this->doTransition($voucher, $this->trader->id)) {
            $this->responses['success_add'][] = $voucher->code;
        }
    }

    /**
     * Confirms a voucher for payment and associates it with the batch StateToken.
     */
    public function handleConfirm(Voucher $voucher, StateToken $stateToken): void
    {
        if ($this->doTransition($voucher, $this->trader->id)) {
            $this->vouchersForPayment[] = $voucher->id;
            $voucher->getPriorState()->stateToken()->associate($stateToken)->save();
        }
    }

    /**
     * Rejects a voucher back to the free pool, resolving the correct rollback
     * transition from the voucher's prior state.
     */
    public function handleReject(Voucher $voucher): void
    {
        $last_state = $voucher->getPriorState();
        if ($last_state === null) {
            $this->responses['failed_reject'][] = $voucher->code;
            return;
        }

        // revert down the correct state machine path
        $transition = 'reject-to-' . $last_state->from;

        if ($this->doTransition($voucher, null, $transition)) {
            $this->responses['success_reject'][] = $voucher->code;
        }
    }

    /**
     * Catchall for any transition string not explicitly handled above.
     */
    public function handleDefault(Voucher $voucher): void
    {
        $this->doTransition($voucher, $this->trader->id);
    }

    /**
     * Emails the trader's users a voucher payment request with an attached report.
     */
    public static function emailVoucherPaymentRequest(Trader $trader, array $vouchers): void
    {
        $title = "A report containing voucher payment request for $trader->name.";
        $date = Carbon::now()->format('d-m-Y');

        $file = TraderController::createVoucherListFile($trader, $vouchers, $title, $date, Auth::user()->name);
        $programme_amounts = TraderController::getProgrammeAmounts($vouchers);

        event(new VoucherPaymentRequested(Auth::user(), $trader, $vouchers, $file, $programme_amounts));
    }

    /**
     * Constructs a human-readable response message for the batch result.
     */
    public function constructResponseMessage(): array
    {
        if (!empty($this->vouchersForPayment)) {
            return ['message' => trans('api.messages.voucher_payment_requested')];
        }

        $total_submitted = 0;
        $error_type = '';
        $responses = $this->responses;

        foreach ($responses as $key => $codes) {
            $total_submitted += count($codes);
            if (count($codes) === 1) {
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
            'message' => trans('api.messages.batch_voucher_submit', [
                'success_amount' => count($responses['success_add']),
                'duplicate_amount' => count($responses['own_duplicate']) + count($responses['other_duplicate']),
                // 'invalid' no longer exists at this layer — callers detect missing
                // codes cheaply via a query builder pluck() before calling handle().
                'invalid_amount' => count($responses['undelivered']),
            ]),
        ];
    }

    /**
     * Works out if we had any type of voucher transition failure
     */
    public function hasFailures(): bool
    {
        return (bool)Arr::first(
            Arr::except($this->responses, 'success_add'),
            static function (array $failureType) {
                return !empty($failureType);
            }
        );
    }

    public function getFailureCodes(): array
    {
        return ($this->hasFailures())
            ? Arr::flatten(Arr::except($this->responses, 'success_add'))
            : [];
    }
}
