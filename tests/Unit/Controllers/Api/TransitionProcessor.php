<?php

namespace Tests\Unit\Controllers\Api;

use App\Events\VoucherPaymentRequested;
use App\Http\Controllers\API\TraderController;
use App\StateToken;
use App\Trader;
use App\Voucher;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Log;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class TransitionProcessor
{
    public array $responses = [
        'success_add' => [],
        'success_reject' => [],
        'own_duplicate' => [],
        'other_duplicate' => [],
        'invalid' => [],
        'failed_reject' => [],
        'undelivered' => [],
    ];

    public array $vouchers_for_payment = [];

    private Trader $trader;

    private Collection $vouchers;

    private string $transition;

    public function __construct(trader $trader, string $transition)
    {
        $this->transition = $transition;
        $this->trader = $trader;
    }

    public function handle(array $voucherCodes)
    {
        // set a lock, to prevent double submits
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $lock = $factory->createLock('transition');

        if ($lock->acquire()) {
            // get and the available vouchers
            $this->vouchers = Voucher::findByCodes($voucherCodes);

            // Get the ones not in that list - they are bad codes.
            // We need to re-key the array here because otherwise the json response will return object for non 0 starting.
            $this->responses['invalid'] = array_values(
                array_diff(
                    $voucherCodes,
                    $this->vouchers->pluck('code')->toArray()
                )
            );

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
        return $this->responses;
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
            }
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
     * @return void
     */
    public function handleConfirm(): void
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
}