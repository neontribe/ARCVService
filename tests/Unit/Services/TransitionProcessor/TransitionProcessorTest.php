<?php

namespace Tests\Unit\Services\TransitionProcessor;

use App\CentreUser;
use App\Events\VoucherPaymentRequested;
use App\Registration;
use App\Services\TransitionProcessor\TransitionProcessor;
use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use App\VoucherState;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Integration tests for TransitionProcessor.
 *
 * These tests exercise the full state-machine round-trip against a real
 * (refreshed) database. All TransitionProcessor instances use
 * sendPaymentEmail = false unless the test specifically targets email dispatch,
 * to avoid the TraderController file-generation side-effect.
 *
 * ── Auth contexts ────────────────────────────────────────────────────────────
 *
 * TransitionProcessor::initStateToken() calls Auth::check() / Auth::id()
 * WITHOUT specifying a guard, so it only sees whoever is on the DEFAULT guard.
 *
 * There are three real callers, each with a different default-guard state:
 *
 *   1. SweepAndSubmitCollectingCentres (console command)
 *      No session, no auth at all. Auth::check() = false.
 *      → StateToken.user_id = null.
 *
 *   2. ProcessTransitionJob (queued job)
 *      Calls Auth::logout() then Auth::login(User::find($runAsId)), placing an
 *      App\User on the DEFAULT guard. Auth::check() = true.
 *      → StateToken.user_id = that User's id.
 *
 *   3. BundleController (HTTP, store guard)
 *      Uses actingAs($centreUser, 'store'), which places a CentreUser on the
 *      STORE guard only. The default guard remains empty.
 *      Auth::check() (default guard) = false.
 *      → StateToken.user_id = null.
 *      NOTE: BundleController only calls the 'collect' transition, so
 *      initStateToken() is never reached from there anyway.
 *
 *   VoucherController (HTTP, API) can call any transition including 'confirm'.
 *   Its auth guard depends on the API auth configuration, but when it does
 *   reach 'confirm' it typically runs under an App\User on the default guard —
 *   the same outcome as case 2.
 *
 *   Consequence: StateToken.user_id is only populated via the job (or API)
 *   path. The store/command contexts correctly produce user_id = null.
 *   Storing a CentreUser id in user_id would be a FK violation because
 *   StateToken.user() is a belongsTo(User::class).
 *
 * ── Auth separation in helpers ───────────────────────────────────────────────
 *
 * The Statable trait records Auth::id() on every VoucherState row it creates.
 * Test helpers that call applyTransition() log in centreUser on the DEFAULT
 * guard for that purpose (matching BundleControllerTest's setUp pattern) and
 * then log out immediately. This leaves the default guard empty when the
 * processor call under test begins, so each test controls exactly which auth
 * context the processor sees.
 *
 * ── Lock note ────────────────────────────────────────────────────────────────
 *
 * TransitionProcessor hardcodes SemaphoreStore (requires the sysvsem
 * extension), making the "cannot acquire lock" branch untestable without
 * refactoring. Recommended fix: inject LockFactory via the constructor.
 */
class TransitionProcessorTest extends TestCase
{
    use RefreshDatabase;

    private Trader $trader;

    /**
     * Used ONLY inside helpers that need a default-guard user for
     * applyTransition(). Never used as the processor's auth context.
     */
    private CentreUser $centreUser;

    /**
     * App\User — the model type ProcessTransitionJob puts on the default guard
     * via Auth::login(User::find($runAsId)).
     */
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin time to a fixed Wednesday noon so that Carbon::yesterday(),
        // Carbon::tomorrow(), and Eloquent's created_at timestamps are all
        // deterministic regardless of when the suite runs. Avoids failures
        // at midnight, around DST transitions, or at week/month boundaries.
        Carbon::setTestNow(Carbon::parse('2024-06-12 12:00:00'));

        $this->trader     = factory(Trader::class)->create();
        $this->centreUser = factory(CentreUser::class)->create();
        $this->user       = factory(User::class)->create();

        // No Auth::login here. Every test starts with the default guard empty
        // and sets up whatever auth context it needs.

        // With time pinned, tomorrow() = 2024-06-13, so freshly created
        // vouchers (created_at = 2024-06-12) do NOT trip the undelivered guard
        // (condition: first_delivery_date <= created_at).
        config(['arc.first_delivery_date' => Carbon::tomorrow()->toDateString()]);
    }

    protected function tearDown(): void
    {
        // Belt-and-braces: prevent auth from bleeding between tests.
        Auth::logout();
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Create a printed voucher and advance it to 'dispatched'.
     *
     * applyTransition() records Auth::id() on the VoucherState row, so a user
     * must be on the default guard while it runs. centreUser is used here
     * (matching BundleControllerTest's setUp pattern) and is logged out before
     * the method returns, leaving the default guard empty for the processor
     * call that follows in each test.
     */
    private function makeDispatchedVoucher(string $code): Voucher
    {
        Auth::login($this->centreUser);
        $voucher = factory(Voucher::class)->state('printed')->create(['code' => $code]);
        $voucher->applyTransition('dispatch');
        Auth::logout();

        return $voucher;
    }

    /**
     * Create a dispatched voucher and advance it to 'recorded' (collected).
     *
     * Same auth discipline as makeDispatchedVoucher: the default guard is
     * empty when the method returns.
     */
    private function makeCollectedVoucher(string $code, ?Trader $trader = null): Voucher
    {
        $trader ??= $this->trader;

        $voucher = $this->makeDispatchedVoucher($code);
        $voucher->delivery_id = 1;

        Auth::login($this->centreUser);
        $voucher->trader_id = $trader->id;
        $voucher->applyTransition('collect');
        Auth::logout();

        return $voucher;
    }

    /**
     * Build an Eloquent Builder scoped to exactly one voucher.
     * Most tests use this to stay independent of the Builder-vs-Relation fix.
     */
    private function queryFor(Voucher $voucher)
    {
        return Voucher::where('id', $voucher->id);
    }

    /**
     * Instantiate a TransitionProcessor.
     * sendPaymentEmail defaults to false to suppress TraderController side-effects.
     */
    private function makeProcessor(
        string $transition,
        ?Trader $trader = null,
        int $chunkSize = 500,
        bool $sendPaymentEmail = false
    ): TransitionProcessor {
        return new TransitionProcessor(
            trader: $trader ?? $this->trader,
            transition: $transition,
            chunkSize: $chunkSize,
            sendPaymentEmail: $sendPaymentEmail,
        );
    }

    // =========================================================================
    // collect — success path
    // =========================================================================

    public function testCollectMovesADispatchedVoucherToRecorded(): void
    {
        $voucher = $this->makeDispatchedVoucher('COL00001');
        $voucher->delivery_id = 1;
        $voucher->save();

        // Auth context: none — mirrors BundleController where the CentreUser
        // is on the store guard only, so Auth::check() (default guard) = false.
        $this->assertFalse(Auth::check());
        $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertSame('recorded', $voucher->fresh()->currentstate);
    }

    public function testCollectSetsTraderIdOnTheVoucher(): void
    {
        $voucher = $this->makeDispatchedVoucher('COL00002');
        $voucher->delivery_id = 1;
        $voucher->save();

        $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertSame($this->trader->id, $voucher->fresh()->trader_id);
    }

    public function testCollectAddsSuccessAddCodeToResponseForEachTransitionedVoucher(): void
    {
        $v1 = $this->makeDispatchedVoucher('COL00003');
        $v2 = $this->makeDispatchedVoucher('COL00004');
        foreach ([$v1, $v2] as $v) {
            $v->delivery_id = 1;
            $v->save();
        }

        $response = $this->makeProcessor('collect')
            ->handle(Voucher::whereIn('id', [$v1->id, $v2->id]));

        $this->assertContains('COL00003', $response->toArray()['success_add']);
        $this->assertContains('COL00004', $response->toArray()['success_add']);
    }

    // =========================================================================
    // collect — undelivered guard
    // =========================================================================

    public function testCollectSkipsAVoucherWithNoDeliveryIdCreatedAfterFirstDeliveryDate(): void
    {
        // Move the delivery date into the past so today's voucher is "new".
        config(['arc.first_delivery_date' => Carbon::yesterday()->toDateString()]);

        $voucher = $this->makeDispatchedVoucher('UDL00001');
        // delivery_id left null; created_at defaults to now().

        $response = $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertSame('dispatched', $voucher->fresh()->currentstate);
        $this->assertContains('UDL00001', $response->toArray()['undelivered']);
    }

    public function testCollectAllowsAVoucherWhenDeliveryIdIsSetRegardlessOfCreatedAt(): void
    {
        config(['arc.first_delivery_date' => Carbon::yesterday()->toDateString()]);

        $voucher = $this->makeDispatchedVoucher('UDL00002');
        $voucher->delivery_id = 1;
        $voucher->save();

        $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertSame('recorded', $voucher->fresh()->currentstate);
    }

    public function testCollectAllowsAVoucherCreatedBeforeFirstDeliveryDateEvenWithoutDeliveryId(): void
    {
        // The guard fires when first_delivery_date <= created_at.
        // A voucher created BEFORE first_delivery_date is safe.
        config(['arc.first_delivery_date' => Carbon::tomorrow()->toDateString()]);

        Auth::login($this->centreUser);
        $voucher = factory(Voucher::class)->state('printed')->create([
            'code'       => 'UDL00003',
            'created_at' => Carbon::yesterday(),
        ]);
        $voucher->applyTransition('dispatch');
        Auth::logout();

        $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertSame('recorded', $voucher->fresh()->currentstate);
    }

    // =========================================================================
    // collect — duplicate detection
    // =========================================================================

    public function testCollectAddsOwnDuplicateWhenTheSameTraderAlreadyOwnsTheVoucher(): void
    {
        $voucher = $this->makeCollectedVoucher('DUP00001');

        $response = $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertContains('DUP00001', $response->toArray()['own_duplicate']);
        $this->assertSame('recorded', $voucher->fresh()->currentstate);
    }

    public function testCollectAddsOtherDuplicateWhenADifferentTraderOwnsTheVoucher(): void
    {
        $otherTrader = factory(Trader::class)->create();
        $voucher     = $this->makeCollectedVoucher('DUP00002', $otherTrader);

        // Processor uses $this->trader, which differs from $otherTrader.
        $response = $this->makeProcessor('collect')->handle($this->queryFor($voucher));

        $this->assertContains('DUP00002', $response->toArray()['other_duplicate']);
    }

    // =========================================================================
    // confirm — state transition
    // =========================================================================

    public function testConfirmMovesARecordedVoucherToPaymentPending(): void
    {
        $voucher = $this->makeCollectedVoucher('CFM00001');

        // Auth context: none — mirrors SweepAndSubmit command.
        $this->assertFalse(Auth::check());
        $this->makeProcessor('confirm')->handle($this->queryFor($voucher));

        $this->assertSame('payment_pending', $voucher->fresh()->currentstate);
    }

    public function testConfirmRecordsVoucherIdsInTheResponseForSubsequentEmailDispatch(): void
    {
        $voucher = $this->makeCollectedVoucher('CFM00002');

        $response = $this->makeProcessor('confirm')->handle($this->queryFor($voucher));

        $this->assertTrue($response->hasPayments());
        $this->assertContains($voucher->id, $response->getVouchersForPayment());
    }

    // =========================================================================
    // confirm — StateToken creation and batch association
    // =========================================================================

    public function testConfirmCreatesExactlyOneStateTokenForAnEntireBatch(): void
    {
        $tokensBefore = StateToken::count();

        foreach (['CFM00003', 'CFM00004', 'CFM00005'] as $code) {
            $this->makeCollectedVoucher($code);
        }

        // Auth context: none — SweepAndSubmit / store-guard context.
        $this->makeProcessor('confirm')
            ->handle(Voucher::whereIn('code', ['CFM00003', 'CFM00004', 'CFM00005']));

        $this->assertSame($tokensBefore + 1, StateToken::count());
    }

    public function testConfirmAssociatesAllVouchersInTheBatchWithTheSameToken(): void
    {
        foreach (['CFM00006', 'CFM00007'] as $code) {
            $this->makeCollectedVoucher($code);
        }

        $this->makeProcessor('confirm')
            ->handle(Voucher::whereIn('code', ['CFM00006', 'CFM00007']));

        $token = StateToken::latest()->first();

        $linkedTokenIds = VoucherState::whereIn(
            'voucher_id',
            Voucher::whereIn('code', ['CFM00006', 'CFM00007'])->pluck('id')
        )
            ->whereNotNull('state_token_id')
            ->pluck('state_token_id')
            ->unique()
            ->values()
            ->all();

        $this->assertCount(1, $linkedTokenIds);
        $this->assertSame($token->id, $linkedTokenIds[0]);
    }

    // =========================================================================
    // confirm — StateToken user_id per caller auth context
    // =========================================================================

    /**
     * SweepAndSubmitCollectingCentres: no session at all.
     * Auth::check() (default guard) = false → user_id must be null.
     */
    public function testConfirmLeavesStateTokenUserIdNullWhenNoAuthContextExists(): void
    {
        $voucher = $this->makeCollectedVoucher('CFM00008');

        $this->assertFalse(Auth::check(), 'Pre-condition: no user on default guard');
        $this->makeProcessor('confirm')->handle($this->queryFor($voucher));

        $this->assertNull(StateToken::latest()->value('user_id'));
    }

    /**
     * ProcessTransitionJob: Auth::login(User::find($runAsId)) puts an App\User
     * on the default guard → user_id must equal that User's id.
     *
     * StateToken.user() is a belongsTo(User::class), so only an App\User id
     * is valid here. A CentreUser id would be a foreign-key violation.
     */
    public function testConfirmSetsStateTokenUserIdWhenAppUserIsOnDefaultGuard(): void
    {
        $voucher = $this->makeCollectedVoucher('CFM00009');

        // Simulate: Auth::login(User::find($this->runAsId)) inside the job.
        Auth::login($this->user);

        $this->makeProcessor('confirm')->handle($this->queryFor($voucher));

        $this->assertSame($this->user->id, StateToken::latest()->value('user_id'));
    }

    /**
     * BundleController: actingAs($centreUser, 'store') places a CentreUser
     * on the STORE guard only — the default guard remains empty.
     * Auth::check() (default guard) = false → user_id must be null.
     *
     * This also guards against writing a CentreUser id into the FK column,
     * which would break StateToken.user() (belongsTo(User::class)).
     */
    public function testConfirmLeavesStateTokenUserIdNullWhenCentreUserIsOnlyOnStoreGuard(): void
    {
        $voucher = $this->makeCollectedVoucher('CFM00010');

        Auth::guard('store')->login($this->centreUser);
        $this->assertFalse(
            Auth::check(),
            'Pre-condition: default guard must not see the store-guard CentreUser'
        );

        $this->makeProcessor('confirm')->handle($this->queryFor($voucher));

        $this->assertNull(StateToken::latest()->value('user_id'));
    }

    // =========================================================================
    // reject
    // =========================================================================

    public function testRejectRollsACollectedVoucherBackToDispatched(): void
    {
        $voucher = $this->makeCollectedVoucher('REJ00001');

        // Auth context: none — BundleController uses the store guard.
        $response = $this->makeProcessor('reject')->handle($this->queryFor($voucher));

        $this->assertSame('dispatched', $voucher->fresh()->currentstate);
        $this->assertContains('REJ00001', $response->toArray()['success_reject']);
    }

    public function testRejectClearsTraderIdOnRollback(): void
    {
        $voucher = $this->makeCollectedVoucher('REJ00002');

        $this->makeProcessor('reject')->handle($this->queryFor($voucher));

        $this->assertNull($voucher->fresh()->trader_id);
    }

    public function testRejectAddsFailedRejectCodeWhenVoucherHasNoPriorState(): void
    {
        // A printed voucher has no VoucherState records, so getPriorState()
        // returns null, hitting the early-return guard in handleReject().
        Auth::login($this->centreUser);
        $voucher = factory(Voucher::class)->state('printed')->create(['code' => 'REJ00003']);
        Auth::logout();

        $response = $this->makeProcessor('reject')->handle($this->queryFor($voucher));

        $this->assertContains('REJ00003', $response->toArray()['failed_reject']);
        $this->assertSame('printed', $voucher->fresh()->currentstate);
    }

    // =========================================================================
    // handle() — Builder vs Relation (regression for the type-hint bug)
    // =========================================================================

    /**
     * TransitionProcessor::handle() declared Builder|Relation but
     * processInChunks() was typed as Builder only. Passing an Eloquent
     * HasMany — exactly what BundleController::collectBundle() does — threw a
     * PHP 8 TypeError, silently rolled back the DB transaction, and left
     * disbursed_at = null.
     *
     * Auth context: none (no default-guard user), matching the real
     * BundleController store-guard context.
     */
    public function testHandleAcceptsAnEloquentHasManyRelationNotJustAQueryBuilder(): void
    {
        $registration = factory(Registration::class)->create();
        $bundle       = $registration->currentBundle();

        $voucher = $this->makeDispatchedVoucher('REL00001');
        $voucher->delivery_id = 1;
        $voucher->bundle()->associate($bundle)->save();

        $this->assertFalse(Auth::check(), 'Pre-condition: default guard empty (store-guard context)');

        // $bundle->vouchers() is a HasMany (Relation), not a Builder.
        $response = $this->makeProcessor('collect')->handle($bundle->vouchers());

        $this->assertSame('recorded', $voucher->fresh()->currentstate);
        $this->assertFalse($response->hasFailures());
    }

    // =========================================================================
    // handle() — chunking
    // =========================================================================

    /**
     * With chunkSize = 2 and 5 vouchers, lazy() issues three SELECT pages.
     * Every voucher must be processed regardless of page boundaries.
     *
     * Auth context: none — mirrors SweepAndSubmit.
     */
    public function testHandleProcessesAllVouchersAcrossMultipleChunks(): void
    {
        $codes = ['CHK00001', 'CHK00002', 'CHK00003', 'CHK00004', 'CHK00005'];

        foreach ($codes as $code) {
            $v = $this->makeDispatchedVoucher($code);
            $v->delivery_id = 1;
            $v->save();
        }

        $response = $this->makeProcessor('collect', chunkSize: 2)
            ->handle(Voucher::whereIn('code', $codes));

        $this->assertCount(5, $response->toArray()['success_add']);

        foreach ($codes as $code) {
            $this->assertSame(
                'recorded',
                Voucher::where('code', $code)->value('currentstate'),
                "Voucher $code was not transitioned"
            );
        }
    }

    // =========================================================================
    // sendPaymentEmail flag
    // =========================================================================

    /**
     * SweepAndSubmitCollectingCentres always passes sendPaymentEmail: false.
     * No VoucherPaymentRequested event should fire regardless of outcome.
     *
     * Auth context: none — mirrors the command.
     */
    public function testNoPaymentEventIsFiredWhenSendPaymentEmailIsFalse(): void
    {
        Event::fake();

        $voucher = $this->makeCollectedVoucher('EML00001');

        $this->makeProcessor('confirm', sendPaymentEmail: false)
            ->handle($this->queryFor($voucher));

        Event::assertNotDispatched(VoucherPaymentRequested::class);
    }
}
