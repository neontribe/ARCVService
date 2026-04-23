<?php

namespace App\Services\TransitionProcessor;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\StateToken;
use App\Trader;
use App\Voucher;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use function Symfony\Component\Translation\t;

class TransitionProcessor
{
    private TransitionResponse $response;

    /**
     * Parsed once at construction — avoids reparsing the config value
     * on every voucher inside handleCollect().
     */
    private Carbon $collectDeliveryDate;

    public function __construct(
        private readonly Trader $trader,
        private readonly string $transition,
        private readonly int $chunkSize = 500,
        private readonly bool $sendPaymentEmail = true
    ) {
        $this->response = new TransitionResponse();
        $this->collectDeliveryDate = Carbon::parse(config('arc.first_delivery_date'));
    }

    /**
     * Accepts a query builder describing the vouchers to transition.
     *
     * The caller is responsible for scoping the query (e.g. by trader, state).
     * This method opens the cursor itself via ->lazy(), so no models are
     * instantiated outside the processor.
     *
     * Returns a TransitionResponse whose message and failure state can be
     * inspected by the caller. Callers that hold user-submitted code strings
     * should call $response->addInvalid($invalidCodes) before reading the
     * response message.
     */
    public function handle(Builder $query): TransitionResponse
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
            $this->response->addCode('own_duplicate', '000000');
            return $this->response;
        }

        try {
            $this->processInChunks($query);
        } finally {
            $lock->release();
        }

        if ($this->sendPaymentEmail && $this->response->hasPayments()) {
            $vouchersForEmail = Voucher::findMany($this->response->getVouchersForPayment())->all();
            Log::info('SENDING MAIL ' . count($vouchersForEmail));
            self::emailVoucherPaymentRequest($this->trader, $vouchersForEmail);
        }

        return $this->response;
    }

    /**
     * Opens a database cursor over the query and processes one voucher at a time.
     *
     * lazy($chunkSize) issues SELECT queries in pages of $chunkSize behind the
     * scenes, but only one model is held in memory at a time from PHP's perspective.
     *
     * The StateToken for confirm transitions is created once here so it spans
     * the entire batch — creating it inside the loop would associate each page
     * of vouchers with a different token and break the payment audit trail.
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
    ): bool {
        $transition = $transition ?: $this->transition;
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
                    $this->response->addCode('own_duplicate', $voucher->code);
                    Log::debug(sprintf(
                        'Transition denied %s on %s for trader %d: own_duplicate',
                        $transition,
                        $voucher,
                        $againstTraderId
                    ));
                } else {
                    // Another trader submitted this voucher, or the state is invalid.
                    $this->response->addCode('other_duplicate', $voucher->code);
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
    private function handleCollect(Voucher $voucher): void
    {
        Log::debug('handleCollect on voucher ' . $voucher->code);

        if (
            $voucher->delivery_id === null &&
            $this->collectDeliveryDate->lessThanOrEqualTo($voucher->created_at)
        ) {
            $this->response->addCode('undelivered', $voucher->code);
            Log::debug('Undelivered voucher ' . $voucher->code);
            return;
        }

        if ($this->doTransition($voucher, $this->trader->id)) {
            $this->response->addCode('success_add', $voucher->code);
        }
    }

    /**
     * Confirms a voucher for payment and associates it with the batch StateToken.
     */
    private function handleConfirm(Voucher $voucher, StateToken $stateToken): void
    {
        if ($this->doTransition($voucher, $this->trader->id)) {
            // Accumulate IDs only — full models are loaded after the loop for the email.
            $this->response->recordPayment($voucher->id);
            $voucher->getPriorState()->stateToken()->associate($stateToken)->save();
        }
    }

    /**
     * Rejects a voucher back to the free pool, resolving the correct rollback
     * transition from the voucher's prior state.
     */
    private function handleReject(Voucher $voucher): void
    {
        $last_state = $voucher->getPriorState();
        if ($last_state === null) {
            $this->response->addCode('failed_reject', $voucher->code);
            return;
        }

        $transition = 'reject-to-' . $last_state->from;

        if ($this->doTransition($voucher, null, $transition)) {
            $this->response->addCode('success_reject', $voucher->code);
        }
    }

    /**
     * Catchall for any transition string not explicitly handled above.
     */
    private function handleDefault(Voucher $voucher): void
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
}
