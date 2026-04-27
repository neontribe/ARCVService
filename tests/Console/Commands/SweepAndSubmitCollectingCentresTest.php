<?php

namespace Tests\Console\Commands;

use App\Centre;
use App\CentreUser;
use App\Events\VoucherPaymentRequested;
use App\Market;
use App\Trader;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tests for the arc:sweep-and-submit Artisan command.
 *
 * The command confirms for payment all vouchers in the 'recorded' state for traders
 * whose market has a non-null centre_id (ie centre-linked markets). It is equivalent
 * to running TransitionProcessor with transition='confirm' and sendPaymentEmail=false
 * for each qualifying trader.
 *
 * ── Data scenario ────────────────────────────────────────────────────────────
 *
 * The command processes vouchers that have already been through the full
 * BundleController::collectBundle() flow:
 *
 *   printed → dispatched → recorded (via 'collect' transition)
 *
 * At the point the command runs, vouchers are in 'recorded' state with
 * trader_id set.  The command advances them to 'payment_pending'.
 *
 * Helper methods mirror the state that BundleController leaves behind without
 * going through the HTTP layer: applyTransition() is called directly on the
 * model, bypassing the processor's undelivered guard (which only applies to
 * 'collect', not 'confirm').
 *
 * ── Auth context ─────────────────────────────────────────────────────────────
 *
 * The command runs with no authenticated user.  Artisan commands have no
 * session, so Auth::check() on the default guard is false throughout.
 * StateToken.user_id is therefore null for all tokens created by this command
 * (confirmed by TransitionProcessorTest::testConfirmLeavesStateTokenUserIdNullWhenNoAuthContextExists).
 *
 * The Statable trait records Auth::id() on VoucherState rows when
 * applyTransition() is called. Test helpers log in centreUser on the default
 * guard for that purpose and log out immediately, leaving the default guard
 * empty before the command runs.
 *
 * ── Relation vs Builder ───────────────────────────────────────────────────────
 *
 * The command passes $trader->vouchers()->where('currentstate', 'recorded')
 * to TransitionProcessor::handle(). This is a constrained HasMany Relation,
 * not a plain Builder. The Builder|Relation type-hint fix in processInChunks()
 * (also required by BundleController) is a pre-condition for this command
 * working correctly.
 */
class SweepAndSubmitCollectingCentresTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Used only inside helpers that call applyTransition(), which records
     * Auth::id() on VoucherState rows. Never used as auth context during the
     * command itself.
     */
    private CentreUser $centreUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin time to a fixed Wednesday noon so that Carbon::yesterday(),
        // Carbon::tomorrow(), and Eloquent's created_at timestamps are all
        // deterministic regardless of when the suite runs. Avoids failures
        // at midnight, around DST transitions, or at week/month boundaries.
        Carbon::setTestNow(Carbon::parse('2024-06-12 12:00:00'));

        $this->centreUser = factory(CentreUser::class)->create();

        // With time pinned, tomorrow() = 2024-06-13, so freshly created
        // vouchers (created_at = 2024-06-12) do NOT trip the undelivered guard
        // during setup helpers that call the 'collect' transition directly.
        config(['arc.first_delivery_date' => Carbon::tomorrow()->toDateString()]);
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Create a Market with centre_id set — qualifies for sweep processing.
     */
    private function makeCentreLinkedMarket(?Centre $centre = null): Market
    {
        $centre ??= factory(Centre::class)->create();
        return factory(Market::class)->create(['centre_id' => $centre->id]);
    }

    /**
     * Create a Market with centre_id null — excluded from sweep processing.
     */
    private function makeStandaloneMarket(): Market
    {
        return factory(Market::class)->create(['centre_id' => null]);
    }

    /**
     * Create a Trader belonging to a centre-linked market.
     */
    private function makeCentreLinkedTrader(?Centre $centre = null): Trader
    {
        return factory(Trader::class)->create([
            'market_id' => $this->makeCentreLinkedMarket($centre)->id,
        ]);
    }

    /**
     * Create a Trader belonging to a market with no centre link.
     */
    private function makeStandaloneTrader(): Trader
    {
        return factory(Trader::class)->create([
            'market_id' => $this->makeStandaloneMarket()->id,
        ]);
    }

    /**
     * Advance a voucher through printed → dispatched → recorded and associate
     * it with the given trader.
     *
     * applyTransition() records Auth::id() on each VoucherState row, so
     * centreUser is logged in on the default guard while the transitions run,
     * then logged out before returning. The command therefore starts with the
     * default guard empty.
     *
     * Note: delivery_id is not set because the undelivered guard only fires
     * during the 'collect' transition inside handleCollect(). For 'confirm'
     * (what the command runs), delivery_id is irrelevant.
     */
    private function makeRecordedVoucher(string $code, Trader $trader): Voucher
    {
        Auth::login($this->centreUser);

        $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
        $voucher->applyTransition('dispatch');
        $voucher->trader_id = $trader->id;
        $voucher->applyTransition('collect'); // → 'recorded'

        Auth::logout();

        return $voucher;
    }

    /**
     * Create a voucher that is dispatched but NOT yet collected — it should
     * remain untouched by the sweep.
     */
    private function makeDispatchedVoucher(string $code, Trader $trader): Voucher
    {
        Auth::login($this->centreUser);

        $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
        $voucher->applyTransition('dispatch');
        $voucher->trader_id = $trader->id;
        $voucher->save();

        Auth::logout();

        return $voucher;
    }

    // =========================================================================
    // Scoping: which traders are processed
    // =========================================================================

    public function testCommandProcessesTraderInCentreLinkedMarket(): void
    {
        $trader = $this->makeCentreLinkedTrader();
        $voucher = $this->makeRecordedVoucher('SWP00001', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        $this->assertSame('payment_pending', $voucher->fresh()->currentstate);
    }

    public function testCommandIgnoresTraderInMarketWithNoCentreLink(): void
    {
        $trader = $this->makeStandaloneTrader();
        $voucher = $this->makeRecordedVoucher('SWP00002', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        // Voucher must remain in 'recorded' — the trader was not selected.
        $this->assertSame('recorded', $voucher->fresh()->currentstate);
    }

    public function testCommandIgnoresTraderWithNoMarket(): void
    {
        // Default factory: market_id = null. whereHas('market', ...) excludes these.
        $trader = factory(Trader::class)->create(['market_id' => null]);
        $voucher = $this->makeRecordedVoucher('SWP00003', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        $this->assertSame('recorded', $voucher->fresh()->currentstate);
    }

    // =========================================================================
    // Skip logic: traders with no recorded vouchers
    // =========================================================================

    public function testCommandSkipsTraderWithNoRecordedVouchersGracefully(): void
    {
        // Trader qualifies by market but has no vouchers at all.
        $this->makeCentreLinkedTrader();

        // Should complete without error.
        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);
    }

    public function testCommandSkipsTraderWhoseVouchersAreNotInRecordedState(): void
    {
        $trader = $this->makeCentreLinkedTrader();
        $voucher = $this->makeDispatchedVoucher('SWP00004', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        // The where('currentstate', 'recorded') filter excludes dispatched vouchers.
        $this->assertSame('dispatched', $voucher->fresh()->currentstate);
    }

    public function testCommandDoesNotHaltWhenOneTraderHasNoVouchersAndAnotherDoes(): void
    {
        // Trader A: centre-linked, no recorded vouchers.
        $this->makeCentreLinkedTrader();

        // Trader B: centre-linked, has a recorded voucher.
        $traderB = $this->makeCentreLinkedTrader();
        $voucherB = $this->makeRecordedVoucher('SWP00005', $traderB);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        // Trader B's voucher must still be processed despite Trader A being skipped.
        $this->assertSame('payment_pending', $voucherB->fresh()->currentstate);
    }

    // =========================================================================
    // Transition correctness
    // =========================================================================

    public function testCommandTransitionsAllRecordedVouchersForATraderToPaymentPending(): void
    {
        $trader = $this->makeCentreLinkedTrader();

        $v1 = $this->makeRecordedVoucher('SWP00006', $trader);
        $v2 = $this->makeRecordedVoucher('SWP00007', $trader);
        $v3 = $this->makeRecordedVoucher('SWP00008', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        foreach ([$v1, $v2, $v3] as $v) {
            $this->assertSame('payment_pending', $v->fresh()->currentstate);
        }
    }

    public function testCommandOnlyConfirmsRecordedVouchersLeavingOtherStatesUntouched(): void
    {
        $trader = $this->makeCentreLinkedTrader();

        $recorded = $this->makeRecordedVoucher('SWP00009', $trader);
        $dispatched = $this->makeDispatchedVoucher('SWP00010', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        $this->assertSame('payment_pending', $recorded->fresh()->currentstate);
        $this->assertSame('dispatched', $dispatched->fresh()->currentstate);
    }

    public function testCommandProcessesMultipleCentreLinkedTradersInASingleRun(): void
    {
        $traderA = $this->makeCentreLinkedTrader();
        $traderB = $this->makeCentreLinkedTrader();

        $vA = $this->makeRecordedVoucher('SWP00011', $traderA);
        $vB = $this->makeRecordedVoucher('SWP00012', $traderB);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        $this->assertSame('payment_pending', $vA->fresh()->currentstate);
        $this->assertSame('payment_pending', $vB->fresh()->currentstate);
    }

    public function testCommandDoesNotConfirmVouchersAlreadyInPaymentPending(): void
    {
        // A voucher already confirmed (e.g. by a previous sweep run) must not
        // appear in the 'recorded' query and must not be transitioned again.
        $trader = $this->makeCentreLinkedTrader();
        $voucher = $this->makeRecordedVoucher('SWP00013', $trader);

        // First run confirms it.
        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);
        $this->assertSame('payment_pending', $voucher->fresh()->currentstate);

        // Second run: the voucher is now payment_pending, not recorded,
        // so the scoped query excludes it. No duplicate transition should occur.
        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);
        $this->assertSame('payment_pending', $voucher->fresh()->currentstate);
    }

    // =========================================================================
    // Isolation: centre-linked vs standalone traders in the same run
    // =========================================================================

    public function testCommandDoesNotConfirmVouchersBelongingToStandaloneTraderEvenWhenCentreLinkedTraderAlsoExists(
    ): void
    {
        $centreLinked = $this->makeCentreLinkedTrader();
        $standalone = $this->makeStandaloneTrader();

        $linkedVoucher = $this->makeRecordedVoucher('SWP00014', $centreLinked);
        $standaloneVoucher = $this->makeRecordedVoucher('SWP00015', $standalone);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        $this->assertSame('payment_pending', $linkedVoucher->fresh()->currentstate);
        $this->assertSame('recorded', $standaloneVoucher->fresh()->currentstate);
    }

    // =========================================================================
    // Email suppression
    // =========================================================================

    /**
     * The command always passes sendPaymentEmail: false.
     * VoucherPaymentRequested must never be fired regardless of how many
     * vouchers are confirmed, matching SweepAndSubmitCollectingCentres's
     * explicit intent to skip email for centre-linked submissions.
     */
    public function testCommandNeverFiresVoucherPaymentRequestedEvent(): void
    {
        Event::fake();

        $trader = $this->makeCentreLinkedTrader();
        $this->makeRecordedVoucher('SWP00016', $trader);

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        Event::assertNotDispatched(VoucherPaymentRequested::class);
    }

    // =========================================================================
    // Relation type passed to TransitionProcessor (regression guard)
    // =========================================================================

    /**
     * The command passes $trader->vouchers()->where('currentstate', 'recorded')
     * to TransitionProcessor::handle(). This is a constrained HasMany Relation,
     * not a plain Builder.
     *
     * Before the Builder|Relation fix in processInChunks(), this threw a
     * TypeError which was swallowed, leaving all vouchers in 'recorded' state.
     * This test failing would indicate the type-hint regression has been
     * reintroduced.
     *
     * @see TransitionProcessorTest::testHandleAcceptsAnEloquentHasManyRelationNotJustAQueryBuilder
     */
    public function testCommandSucceedsWhenProcessorReceivesAConstrainedHasManyRelation(): void
    {
        $trader = $this->makeCentreLinkedTrader();
        $voucher = $this->makeRecordedVoucher('SWP00017', $trader);

        // The command internally builds: $trader->vouchers()->where('currentstate', 'recorded')
        // That is a HasMany Relation with an appended where() — not a Builder.
        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        $this->assertSame(
            'payment_pending',
            $voucher->fresh()->currentstate,
            'processInChunks() must accept a Relation, not just a Builder'
        );
    }

    // =========================================================================
    // Chunk boundary
    // =========================================================================

    /**
     * The command calls Trader::chunk(50, ...). This test verifies that
     * traders beyond the first page of 50 are still processed.
     *
     * Creating 55 traders is expensive; if run time becomes a concern, reduce
     * $count and lower the chunk size by binding a custom command in the
     * service container with a smaller chunk value, or extract chunk size as
     * a constructor parameter.
     */
    public function testCommandProcessesTradersBeyondTheFirstChunkOf50(): void
    {
        // Create 55 centre-linked traders, each with one recorded voucher,
        // to force two chunk() pages.
        $lastVoucher = null;

        for ($i = 1; $i <= 55; $i++) {
            $trader = $this->makeCentreLinkedTrader();
            $lastVoucher = $this->makeRecordedVoucher(
                sprintf('CHK%05d', $i),
                $trader
            );
        }

        $this->artisan('arc:sweep-and-submit')->assertExitCode(0);

        // Spot-check: the final trader (on the second chunk page) was processed.
        $this->assertSame('payment_pending', $lastVoucher->fresh()->currentstate);
    }
}
