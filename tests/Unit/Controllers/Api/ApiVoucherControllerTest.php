<?php

namespace Tests\Unit\Controllers\Api;

use App\Centre;
use App\Delivery;
use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use SM\SMException;
use Tests\TestCase;

class ApiVoucherControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected $trader;
    protected $user;
    protected $vouchers;

    public function setUp(): void
    {
        parent::setUp();

        // Create a Trader
        $this->trader = factory(Trader::class)->create();

        // Create a user on that trader
        $this->user = factory(User::class)->create();
        $this->user->traders()->sync([$this->trader->id]);

        Auth::login($this->user);

        // Create some vouchers at printed state
        $this->vouchers = factory(Voucher::class, 'printed', 10)->create();
    }

    /**
     * Transition to delivery
     *
     * @param $vouchers
     * @param Centre|null $centre
     * @param Carbon|null $deliveryDate
     */
    private function dispatchVouchers($vouchers, Centre $centre, Carbon $deliveryDate = null)
    {
        $deliveryDate = $deliveryDate ?? Carbon::today();

        // Make a delivery
        $delivery = factory(Delivery::class)->create([
                'centre_id' => $centre->id,
                'dispatched_at' => $deliveryDate,
            ]);

        // Update the transition and add delivery
        $vouchers->each(function ($voucher) use ($delivery) {
            $voucher->delivery_id = $delivery->id;
            // Saves voucher
            $voucher->applyTransition('dispatch');
        });
    }

    /** @test */
    public function testItNeverTidiesOldTokensOnConfirmTransitions()
    {
        // Create a Centre
        $centre = factory(Centre::class)->create();

        // Dispatch the first one.
        $this->dispatchVouchers(
            $this->vouchers->slice(0, 1),
            $centre
        );

        // Shift a voucher off to be our oldVoucher.
        $oldVoucher = $this->vouchers->shift();

        // Create some younger tokens
        // Progress some vouchers to recorded state via the controller;
        $data = [
            "trader_id" => 1,
            "transition" => 'collect',
            "vouchers" => [ $oldVoucher->code ]
        ];
        $route = route('api.voucher.transition');
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;

        // There should be no token for this request
        $this->assertEquals(0, StateToken::all()->count());

        // Change the data for confirm
        $data["transition"] = 'confirm';
        // Resubmit
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;
        // There should be a token for this request
        $this->assertEquals(1, StateToken::all()->count());

        // Age the StateToken;
        $oldDate = Carbon::today()->subDays(31);
        $oldVoucherStateToken = $oldVoucher
            ->getPriorState()
            ->stateToken()->first();
        $oldVoucherStateToken->created_at = $oldDate;
        $oldVoucherStateToken->save();
        $oldVoucherStateToken->fresh();
        $this->assertEquals($oldDate, $oldVoucherStateToken->created_at);

        // Confirm the rest of the vouchers
        $data = [
            "trader_id" => 1,
            "transition" => 'collect',
            "vouchers" => $this->vouchers->pluck('code')->toArray()
        ];
        $route = route('api.voucher.transition');
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;
        // Change the data for confirm
        $data["transition"] = 'confirm';
        // Resubmit
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;
        // $oldVoucher should still have a token;
        $oldVoucherStateToken = $oldVoucher
            ->getPriorState()
            ->stateToken()->first();
        $this->assertNotNull($oldVoucherStateToken);

        // There should still be exactly 2 tokens
        $this->assertEquals(2, StateToken::all()->count());
    }

    /** @test */
    public function testItAttachesTokensToPaymentPendingStates()
    {
        // Create a Centre
        $centre = factory(Centre::class)->create();

        // Dispatch the vouchers
        $this->dispatchVouchers(
            $this->vouchers,
            $centre
        );

        // Progress some vouchers to recorded state via the controller;
        $data = [
            "trader_id" => 1,
            "transition" => 'collect',
            "vouchers" => $this->vouchers->pluck('code')->toArray()
        ];

        $route = route('api.voucher.transition');

        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;

        // See there are no Tokens
        $this->assertEquals(0, StateToken::all()->count());

        // Change the data for confirm
        $data["transition"] = 'confirm';

        // Resubmit
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;

        // There should be a token for this request
        $this->assertEquals(1, StateToken::all()->count());
        $stateToken = StateToken::first();

        // The token should be attached to payment pending results for these vouchers.
        $this->vouchers
            ->each(function ($voucher) use ($stateToken) {
                $voucherState = $voucher->getPriorState();
                $this->assertEquals($voucherState->to, 'payment_pending');
                $this->assertEquals($voucherState->stateToken->id, $stateToken->id);
            });
    }

    /** @test */
    public function testItReturnsArrayOfUndeliveredVouchers()
    {
        // Create a Centre
        $centre = factory(Centre::class)->create();

        // Dispatch all except the last one
        $this->dispatchVouchers(
            $this->vouchers->slice(0, 9),
            $centre
        );

        // Progress some vouchers to recorded state via the controller
        $data = [
            "trader_id" => 1,
            "transition" => 'collect',
            "vouchers" => $this->vouchers->pluck('code')->toArray()
        ];

        $route = route('api.voucher.transition');

        $expectedCounts = [
            // The last one is undelivered.
            'success_amount' => $this->vouchers->count()-1,
            'duplicate_amount' => 0,
            // The invalid one is the one hat was undelivered.
            'invalid_amount' => 1,
        ];

        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
            ->assertJson(['message' => trans('api.messages.batch_voucher_submit', $expectedCounts)])
        ;
    }

    /** @test */
    public function testItCannotTransitionFromSameState()
    {
        // Start off with collect transition (printed or dispatched)
        $data = [
            "trader_id" => 1,
            "transition" => 'collect',
            "vouchers" => $this->vouchers->pluck('code')->toArray()
        ];
        $transition = 'collect';
        $this->vouchers
            ->each(function ($voucher) use ($transition) {
                $voucher->applyTransition($transition);
                \Log::info($voucher);
            });
        $route = route('api.voucher.transition');
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;

        // Transition to confirm, which changes it to payment_pending
        $data = [
            "trader_id" => 1,
            "transition" => 'confirm',
            "vouchers" => $this->vouchers->pluck('code')->toArray()
        ];
        $transition = 'confirm';
        $this->vouchers
            ->each(function ($voucher) use ($transition) {
                $voucher->applyTransition($transition);
                \Log::info($voucher);
            });
        $route = route('api.voucher.transition');
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;

        // Try to transition to confirm again and expect the exception message from SMException. This exception
        // means it is not possible to change from payment_pending to payment_pending (which is what we want to happen).
        $data = [
            "trader_id" => 1,
            "transition" => 'confirm',
            "vouchers" => $this->vouchers->pluck('code')->toArray()
        ];
        $transition = 'confirm';
        $this->vouchers
            ->each(function ($voucher) use ($transition) {
                $this->expectExceptionMessage('Transition "confirm" cannot be applied on state "payment_pending" of object "App\Voucher" with graph "Voucher".');
                $this->expectException(SMException::class);
                $voucher->applyTransition($transition);
                \Log::info($voucher);
            });
        $route = route('api.voucher.transition');
        $this->actingAs($this->user, 'api')
            ->json('POST', $route, $data)
            ->assertStatus(200)
        ;
    }
}
