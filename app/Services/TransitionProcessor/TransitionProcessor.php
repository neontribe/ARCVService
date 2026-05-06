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
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class TransitionProcessor
{
    private TransitionResponse $response;

    /**
     * Parsed once at construction — avoids reparsing the config value
     * on every voucher inside handleCollect().
     */
    private Carbon $collectDeliveryDate;

    /**
     * $trader is nullable because payout and reject are admin-driven operations
     * that do not need a trader context:
     *
     *   - handlePayout preserves the voucher's existing trader_id unchanged.
     *   - handleReject clears trader_id explicitly after the rollback transition.
     *   - handleDefault omits trader_id entirely — it does not write trader
     *     context to the voucher. Callers that need trader association must
     *     use an explicit match arm (collect, confirm) rather than relying
     *     on the catchall.
     *
     * There is no longer a null-trader guard in handleDefault. If a new
     * transition requires trader context, add an explicit handler for it.
     */
    public function __construct(
        private readonly ?Trader $trader,
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
    public function handle(Builder|Relation $query): TransitionResponse
    {
        $lock = (new LockFactory(new SemaphoreStore()))->createLock('transition');

        Log::debug(sprintf(
            'Acquiring lock for transition [%s] on trader %s, %d vouchers',
            $this->transition,
            $this->trader?->id ?? 'none',
            $query->count()
        ));

        if (!$lock->acquire()) {
            Log::info(sprintf(
                'Unable to acquire lock in TransitionProcessor for trader %s doing %s',
                $this->trader?->id ?? 'none',
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

        if ($this->sendPaymentEmail && $this->trader !== null && $this->response->hasPayments()) {
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
    private function processInChunks(Builder|Relation $query): void
    {
        $stateToken = $this->transition === 'confirm' ? $this->initStateToken() : null;

        foreach ($query->lazy($this->chunkSize) as $voucher) {
            match ($this->transition) {
                'collect' => $this->handleCollect($voucher),
                'confirm' => $this->handleConfirm($voucher, $stateToken),
                'reject' => $this->handleReject($voucher),
                'payout' => $this->handlePayout($voucher),
                default => $this->handleDefault($voucher),
            };
        }
    }

    /**
     * Creates a StateToken and associates the authenticated user if present.
     */
    private function initStateToken(): StateToken
    {
        $stateToken = StateToken::create([
            'uuid' => StateToken::generateUnusedToken(),
        ]);
        if (Auth::check()) {
            $stateToken->user_id = Auth::id();
            $stateToken->save();
        }
        return $stateToken;
    }

    /**
     * Attempts to apply a transition to a voucher, routing failures into the
     * appropriate response bucket. Returns true only when the transition was applied.
     *
     * trader_id is only written when $againstTraderId is explicitly provided.
     * Callers that do not need to change the trader (payout) omit it by passing
     * null. Callers that need to clear it (reject) do so explicitly after this
     * method returns rather than relying on null as a dual-purpose signal.
     */
    private function doTransition(
        Voucher $voucher,
        string $transition,
        ?int $againstTraderId = null
    ): bool {
        try {
            if ($voucher->transitionAllowed($transition)) {
                // trader_id is set before the transition so that postTransition's $model->save()
                // persists it in the same write as the state change. No explicit save is needed here.
                if ($againstTraderId !== null) {
                    $voucher->trader_id = $againstTraderId;
                }
                $voucher->applyTransition($transition);
                Log::debug(sprintf(
                    'Transition %s on %s for trader %s',
                    $transition,
                    $voucher,
                    $againstTraderId ?? $voucher->trader_id ?? 'none'
                ));
            } else {
                if ($voucher->trader_id === $againstTraderId) {
                    // This trader has already submitted this voucher.
                    $this->response->addCode('own_duplicate', $voucher->code);
                    Log::debug(sprintf(
                        'Transition denied %s on %s for trader %s: own_duplicate',
                        $transition,
                        $voucher,
                        $againstTraderId ?? $voucher->trader_id ?? 'none'
                    ));
                } else {
                    // Another trader submitted this voucher, or the state is invalid.
                    $this->response->addCode('other_duplicate', $voucher->code);
                    Log::debug(sprintf(
                        'Transition denied %s on %s for trader %s: other_duplicate',
                        $transition,
                        $voucher,
                        $againstTraderId ?? $voucher->trader_id ?? 'none'
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

        if ($this->doTransition($voucher, $this->transition, $this->trader->id)) {
            $this->response->addCode('success_add', $voucher->code);
        }
    }

    /**
     * Confirms a voucher for payment and associates it with the batch StateToken.
     */
    private function handleConfirm(Voucher $voucher, StateToken $stateToken): void
    {
        if ($this->doTransition($voucher, $this->transition, $this->trader->id)) {
            // Accumulate IDs only — full models are loaded after the loop for the email.
            $this->response->recordPayment($voucher->id);
            $voucher->getPriorState()->stateToken()->associate($stateToken)->save();
        }
    }

    /**
     * Rejects a voucher back to the free pool, resolving the correct rollback
     * transition from the voucher's prior state.
     *
     * trader_id is cleared explicitly here after the transition rather than
     * relying on null being passed into doTransition — null means "do not
     * touch trader_id", so the clearance must be an intentional separate step.
     */
    private function handleReject(Voucher $voucher): void
    {
        $last_state = $voucher->getPriorState();
        if ($last_state === null) {
            $this->response->addCode('failed_reject', $voucher->code);
            return;
        }

        // doTransition passes null so postTransition does not clear trader_id —
        // the listener only saves whatever is dirty at transition time.
        // An explicit save is required here to persist the clearance afterwards.
        if ($this->doTransition($voucher, 'reject-to-' . $last_state->from, null)) {
            $voucher->trader_id = null;
            $voucher->save();
            $this->response->addCode('success_reject', $voucher->code);
        }
    }

    /**
     * Pays a voucher.
     *
     * Passes $voucher->trader_id rather than $this->trader->id because payout
     * is admin-driven — the trader that originally collected the voucher must
     * not be overwritten. Trader is not required on the processor for this transition.
     */
    private function handlePayout(Voucher $voucher): void
    {
        if ($this->doTransition($voucher, 'payout')) {
            $this->response->addCode('success_add', $voucher->code);
        }
    }

    /**
     * Catchall for any transition string not explicitly handled above.
     *
     * trader_id is intentionally not written here. The only transition that
     * should set trader_id is collect (via handleCollect), and the only one
     * that should clear it is reject (via handleReject). All other transitions
     * leave trader_id untouched.
     *
     * $trader is still required on the processor when reaching this path —
     * not to write to the voucher, but because any transition routed here
     * is assumed to be trader-context-scoped. If a genuinely
     * trader-free transition is added in future, give it its own match arm.
     */
    private function handleDefault(Voucher $voucher): void
    {
        if ($this->trader === null) {
            throw new \LogicException(sprintf(
                'Transition "%s" reached handleDefault with no trader on the processor. ' .
                'Add an explicit match arm in processInChunks() if this transition ' .
                'is intentionally trader-free.',
                $this->transition
            ));
        }

        if ($this->doTransition($voucher, $this->transition)) {
            $this->response->addCode('success_add', $voucher->code);
        }
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
