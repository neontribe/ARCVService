<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Http\Controllers\Service\Admin\PaymentsController;
use App\Sponsor;
use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\StoreTestCase;

class PaymentControllerTest extends StoreTestCase
{
    use RefreshDatabase;

    protected $admin_user;
    protected $trader;
    protected $vouchers;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin_user = factory(AdminUser::class)->create();

        // Default trader for HTTP tests (show, update). Has no market intentionally —
        // those tests don't call makePaymentDataStructure and don't need one.
        $this->trader = factory(Trader::class)->create();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Creates a StateToken with a known user, creates $count payment_pending
     * vouchers via the factory state, associates them with the token, and
     * returns the token loaded with all payment relations.
     *
     * Uses the 'withnullable' Trader state so that market and market.sponsor
     * are populated — makePaymentDataStructure accesses both when building
     * the marketName and area fields.
     *
     * Uses the 'payment_pending' Voucher factory state rather than manually
     * chaining applyTransition() calls — the factory inserts VoucherState rows
     * directly which is sufficient for data-structure tests that are not
     * exercising the state machine itself.
     */
    private function createStateTokenWithPaymentPendingVouchers(int $count = 3): StateToken
    {
        $token = factory(StateToken::class)->create([
            'user_id' => factory(User::class)->create()->id,
        ]);

        // withnullable creates a Market and picks/creates a Sponsor, giving us
        // the full trader → market → sponsor chain that makePaymentDataStructure needs.
        $trader = factory(Trader::class)->state('withnullable')->create();
        $sponsor = factory(Sponsor::class)->create();

        $vouchers = factory(Voucher::class, $count)->state('payment_pending')->create([
            'sponsor_id' => $sponsor->id,
            'trader_id' => $trader->id,
        ]);

        foreach ($vouchers as $k => $voucher) {
            $voucher->code = 'DST' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $voucher->save();

            $voucherState = $voucher->paymentPendedOn()->first();
            $voucherState->state_token_id = $token->id;
            $voucherState->save();
        }

        return StateToken::withPaymentRelations()->find($token->id);
    }

    // =========================================================================
    // show — existing tests
    // =========================================================================

    public function testItReturnsASpecificPaymentRequest(): void
    {
        $token = factory(StateToken::class)->create();
        $s = factory(Sponsor::class)->create();

        $this->vouchers = factory(Voucher::class, 5)->state('printed')->create();
        foreach ($this->vouchers as $k => $voucher) {
            $voucher->code = 'RVNT' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $voucher->sponsor_id = $s->id;
            $voucher->trader_id = $this->trader->id;
            $voucher->applyTransition('dispatch');
            $voucher->applyTransition('collect');
            $voucher->applyTransition('confirm');

            $voucherState = $voucher->paymentPendedOn()->first();
            $voucherState->state_token_id = $token->id;
            $voucherState->voucher_id = $voucher->id;
            $voucherState->save();
            $voucher->save();
        }

        $route = route('admin.payment-request.show', ['paymentUuid' => $token->uuid]);

        $this->actingAs($this->admin_user, 'admin')
            ->get($route)
            ->assertResponseStatus(200);

        foreach ($this->vouchers as $voucher) {
            $this->see($voucher->code);
        }
    }

    // =========================================================================
    // show — new tests
    // =========================================================================

    /**
     * An unknown UUID renders the paymentRequest view with an inline error —
     * it does not 404 or redirect. The view displays the error message when
     * state_token is null, allowing the admin to see they followed a stale link.
     */
    public function testShowRendersInlineErrorForAnUnknownUuid(): void
    {
        $this->actingAs($this->admin_user, 'admin')
            ->get(route('admin.payment-request.show', ['paymentUuid' => 'does-not-exist']))
            ->assertResponseStatus(200)
            ->see('This payment request is invalid, or has expired.');
    }

    // =========================================================================
    // update — existing tests
    // =========================================================================

    public function testItUpdatesASpecificPaymentRequest(): void
    {
        $token = factory(StateToken::class)->create();
        $s = factory(Sponsor::class)->create();
        $u = factory(User::class)->create();

        $this->vouchers = factory(Voucher::class, 5)->state('printed')->create();
        foreach ($this->vouchers as $k => $voucher) {
            $voucher->code = 'RVNT' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $voucher->sponsor_id = $s->id;
            $voucher->trader_id = $this->trader->id;
            $voucher->applyTransition('dispatch');
            $voucher->applyTransition('collect');
            $voucher->applyTransition('confirm');

            $voucherState = $voucher->paymentPendedOn()->first();
            $voucherState->state_token_id = $token->id;
            $voucherState->voucher_id = $voucher->id;
            $voucherState->user_id = $u->id;
            $voucherState->save();
            $voucher->save();
        }

        $route = route('admin.payment-request.update', ['paymentUuid' => $token->uuid]);

        $this->actingAs($this->admin_user, 'admin')
            ->put($route)
            ->followRedirects()
            ->assertResponseStatus(200)
            ->seePageIs(route('admin.payments.index'))
            ->see('Vouchers Paid!');
    }

    // =========================================================================
    // update — new tests
    // =========================================================================

    public function testUpdateReturns404ForAnUnknownUuid(): void
    {
        $this->actingAs($this->admin_user, 'admin')
            ->put(route('admin.payment-request.update', ['paymentUuid' => 'does-not-exist']))
            ->assertResponseStatus(404);
    }

    /**
     * When the payout transition is denied (vouchers already reimbursed),
     * the controller must NOT stamp admin_user_id on the StateToken and must
     * redirect with errors rather than the success notification.
     *
     * Uses the 'reimbursed' Voucher factory state to avoid manually chaining
     * all transitions — the factory inserts the required VoucherState history
     * directly, including the confirm row that paymentPendedOn() finds.
     */
    public function testUpdateRedirectsWithErrorsAndDoesNotStampAdminUserIdWhenTransitionFails(): void
    {
        $token = factory(StateToken::class)->create();
        $s = factory(Sponsor::class)->create();

        // Vouchers already in 'reimbursed' state — payout will be denied.
        $this->vouchers = factory(Voucher::class, 2)->state('reimbursed')->create([
            'sponsor_id' => $s->id,
            'trader_id' => $this->trader->id,
        ]);

        foreach ($this->vouchers as $k => $voucher) {
            $voucher->code = 'FAIL' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $voucher->save();

            // Associate the confirm VoucherState with the token so the
            // whereHas query in update() can locate these vouchers.
            $voucherState = $voucher->paymentPendedOn()->first();
            $voucherState->state_token_id = $token->id;
            $voucherState->save();
        }

        $route = route('admin.payment-request.update', ['paymentUuid' => $token->uuid]);

        $this->actingAs($this->admin_user, 'admin')
            ->put($route)
            ->followRedirects()
            ->assertResponseStatus(200)
            ->seePageIs(route('admin.payments.index'));

        $this->dontSee('Vouchers Paid!');

        $this->assertNull(
            StateToken::find($token->id)->admin_user_id
        );
    }

    // =========================================================================
    // makePaymentDataStructure — unit tests (direct static calls, no HTTP)
    //
    // These tests call the method directly to isolate its logic from the HTTP
    // layer. They live here because the method is static on PaymentsController.
    // If it is ever extracted to a dedicated presenter class, move them with it.
    // =========================================================================

    public function testMakePaymentDataStructureReturnsEmptyArrayForEmptyCollection(): void
    {
        $result = PaymentsController::makePaymentDataStructure(new Collection());

        $this->assertSame([], $result);
    }

    public function testMakePaymentDataStructureKeysResultByTokenUuidWithCorrectShape(): void
    {
        $stateToken = $this->createStateTokenWithPaymentPendingVouchers(2);

        $result = PaymentsController::makePaymentDataStructure(new Collection([$stateToken]));

        $this->assertArrayHasKey($stateToken->uuid, $result);

        $entry = $result[$stateToken->uuid];
        $this->assertSame($stateToken->user->name, $entry['requestedBy']);
        $this->assertSame(2, $entry['vouchersTotal']);
        $this->assertNotEmpty($entry['traderName']);
        $this->assertNotEmpty($entry['marketName']);
        $this->assertNotEmpty($entry['area']);
        $this->assertIsArray($entry['voucherAreas']);
        $this->assertNotEmpty($entry['voucherAreas']);
    }

    public function testMakePaymentDataStructureVoucherAreasCountsVouchersBySponsorName(): void
    {
        $stateToken = $this->createStateTokenWithPaymentPendingVouchers(3);

        $result = PaymentsController::makePaymentDataStructure(new Collection([$stateToken]));

        $voucherAreas = $result[$stateToken->uuid]['voucherAreas'];

        // All three vouchers share the same sponsor created in the helper,
        // so there should be exactly one area key with a count of 3.
        $this->assertCount(1, $voucherAreas);
        $this->assertSame(3, array_values($voucherAreas)[0]);
    }

    public function testMakePaymentDataStructureSkipsTokenWhoseFirstVoucherHasNoTrader(): void
    {
        // A token with no linked voucher states has no trader on first() → null.
        $token = factory(StateToken::class)->create([
            'user_id' => factory(User::class)->create()->id,
        ]);

        $tokenWithNoStates = StateToken::withPaymentRelations()->find($token->id);

        $result = PaymentsController::makePaymentDataStructure(new Collection([$tokenWithNoStates]));

        $this->assertArrayNotHasKey($token->uuid, $result);
    }

    public function testMakePaymentDataStructureSkipsTokenWhoseTraderHasNoMarket(): void
    {
        // Default Trader factory has market_id = null. Without the null market
        // guard in makePaymentDataStructure this would throw a null pointer.
        $token = factory(StateToken::class)->create(['user_id' => factory(User::class)->create()->id]);
        $trader = factory(Trader::class)->create();   // no market
        $sponsor = factory(Sponsor::class)->create();

        $voucher = factory(Voucher::class)->state('payment_pending')->create([
            'sponsor_id' => $sponsor->id,
            'trader_id' => $trader->id,
        ]);

        $voucherState = $voucher->paymentPendedOn()->first();
        $voucherState->state_token_id = $token->id;
        $voucherState->save();

        $loaded = StateToken::withPaymentRelations()->find($token->id);
        $result = PaymentsController::makePaymentDataStructure(new Collection([$loaded]));

        // Token is skipped — no exception thrown.
        $this->assertArrayNotHasKey($token->uuid, $result);
    }

    public function testMakePaymentDataStructureUsesSystemFallbackWhenUserIsNull(): void
    {
        // Token with null user_id — user relationship resolves to null.
        // withnullable trader provides the market → sponsor chain the method needs.
        $token = factory(StateToken::class)->create(['user_id' => null]);
        $trader = factory(Trader::class)->state('withnullable')->create();
        $sponsor = factory(Sponsor::class)->create();

        $voucher = factory(Voucher::class)->state('payment_pending')->create([
            'sponsor_id' => $sponsor->id,
            'trader_id' => $trader->id,
        ]);

        $voucherState = $voucher->paymentPendedOn()->first();
        $voucherState->state_token_id = $token->id;
        $voucherState->save();

        $loaded = StateToken::withPaymentRelations()->find($token->id);
        $result = PaymentsController::makePaymentDataStructure(new Collection([$loaded]));

        $this->assertArrayHasKey($token->uuid, $result);
        $this->assertSame('System', $result[$token->uuid]['requestedBy']);
    }
}
