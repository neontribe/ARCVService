<?php

namespace Tests\Unit;

use App\AdminUser;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Voucher;
use App\VoucherState;
use App\StateToken;
use App\User;
use Auth;

// We might move these out of Model tests - as they are really StateMachine tests.
class VoucherStateModelTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected $marketUser;
    protected $adminUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->marketUser = factory(User::class)->create();
        $this->adminUser = factory(AdminUser::class)->create();
    }

    /** @test */
    public function testProgressVoucherState()
    {

        // We need an auth's user to progress the voucher states.
        Auth::login($this->marketUser);

        $voucher = factory(Voucher::class, 'printed')->create();

        $voucher->applyTransition('dispatch');

        // Should be 1 event.
        $this->assertEquals('dispatched', $voucher->currentstate);
        $this->assertEquals(1, $voucher->history()->count());
    }

    /** @test */
    public function testTransitionAllowed()
    {
        // We need an auth's user to progress the voucher states.
        Auth::login($this->marketUser);

        $voucher = factory(Voucher::class, 'printed')->create();

        // Can we progress to the next step? printed->dispatched
        $this->assertTrue($voucher->transitionAllowed('dispatch'));
        // Can we jump a few steps?
        $this->assertFalse($voucher->transitionAllowed('confirm'));
    }

    /**
     * @test
     */
    public function testInvalidTransition()
    {
        $this->expectException(\SM\SMException::class);
        // We need an auth's user to progress the voucher states.
        Auth::login($this->marketUser);

        $voucher = factory(Voucher::class, 'printed')->create();
        // This will throw exception $this->expectException() wasn't defined
        // But using the function annotation @expectedException works.
        $voucher->state('confirm');
    }

    /** @test */
    public function testAPrintedVoucherCanBeCollected()
    {
        Auth::login($this->marketUser);
        $voucher = factory(Voucher::class, 'printed')->create();

        $voucher->applyTransition('collect');

        $this->assertEquals($voucher->currentstate, 'recorded');
    }

    /** @test */
    public function testADispatchedVoucherCanBeCollected()
    {
        Auth::login($this->marketUser);
        $voucher = factory(Voucher::class, 'printed')->create();

        $voucher->applyTransition('dispatch');
        $voucher->applyTransition('collect');

        $this->assertEquals($voucher->currentstate, 'recorded');
    }

    /** @test */
    public function testOnlyADispatchedVoucherCanBeExpiredOrVoided()
    {
        Auth::login($this->marketUser);
        $v = factory(Voucher::class, 'printed')->create();
        $this->assertEquals($v->currentstate, 'printed');

        // Cant get there from printed
        $this->assertFalse($v->transitionAllowed("expire"));
        $this->assertFalse($v->transitionAllowed("void"));

        $route = [
            'dispatch' => 'dispatched',
            'collect' => 'recorded',
            'confirm' =>'payment_pending',
            'payout' => 'reimbursed',
        ];

        // Lets follow that route and see if we can fall off it.
        foreach ($route as $transition => $state) {
            $v->applyTransition($transition);
            $this->assertEquals($v->currentstate, $state);
            if ($state === 'dispatched') {
                $this->assertTrue($v->transitionAllowed("expire"));
                $this->assertTrue($v->transitionAllowed("void"));
            } else {
                $this->assertFalse($v->transitionAllowed("expire"));
                $this->assertFalse($v->transitionAllowed("void"));
            }
        }
    }

    /** @test */
    public function testAnExpiredOrVoidedVoucherCanBeRetired()
    {
        Auth::login($this->marketUser);
        $vouchers = factory(Voucher::class, 'printed', 2)
            ->create()
            ->each(function ($voucher) {
                $voucher->applyTransition('dispatch');
            });

        $v1 = $vouchers->first();
        $v2 = $vouchers->last();

        $v1->applyTransition('expire');
        $this->assertEquals($v1->currentstate, 'expired');
        $this->assertTrue($v1->transitionAllowed("retire"));
        $v1->applyTransition('retire');
        $this->assertEquals($v1->currentstate, 'retired');

        $v2->applyTransition('void');
        $this->assertEquals($v2->currentstate, 'voided');
        $this->assertTrue($v2->transitionAllowed("retire"));
        $v2->applyTransition('retire');
        $this->assertEquals($v2->currentstate, 'retired');
    }
    /** @test */
    public function testARecordedVoucherCanBeRejectedBackToPrinted()
    {
        Auth::login($this->marketUser);
        $voucher = factory(Voucher::class, 'printed')->create();

        $voucher->applyTransition('collect');
        $voucher->applyTransition('reject-to-printed');

        $this->assertEquals($voucher->currentstate, 'printed');
    }

    /** @test */
    public function testARecordedVoucherCanBeRejectedBackToDispatched()
    {
        Auth::login($this->marketUser);
        $voucher = factory(Voucher::class, 'printed')->create();

        $voucher->applyTransition('dispatch');
        $voucher->applyTransition('collect');
        $voucher->applyTransition('reject-to-dispatched');

        $this->assertEquals($voucher->currentstate, 'dispatched');
    }

    /** @test */
    public function testAVoucherMayHaveAStateToken()
    {
        // Make a voucher
        Auth::login($this->marketUser);
        $voucher = factory(Voucher::class, 'printed')->create();
        $voucher->applyTransition('dispatch');
        $voucher->applyTransition('collect');
        $voucher->applyTransition('confirm');

        // See it's state doesn't, by default get a state token
        /** @var VoucherState $state */
        $state = $voucher->history->last();
        $this->assertTrue(empty($state->stateToken));

        $stateToken = new StateToken();
        $stateToken->uuid = "aStringOfCharacters";
        $stateToken->save();
        // Create and associate one
        $state->stateToken()->associate($stateToken);
        // See that it has one
        $this->assertFalse(empty($state->stateToken));
        $this->assertEquals("aStringOfCharacters", $state->stateToken->uuid);
    }

    /** @test */
    public function testItCanBatchInsertVoucherStates()
    {
        // Make a 100 vouchers
        Auth::login($this->adminUser);
        $vouchers = factory(Voucher::class, 'printed', 100)->create();

        // Dispatch them the normal way
        foreach ($vouchers as $voucher) {
            $voucher->applyTransition('dispatch');
        }

        // Check they're Dispatched.
        $this->assertequals(100, VoucherState::where('to', 'dispatched')->count());

        // Batch transition
        $now = Carbon::now();
        $user_id = auth()->user()->id;
        $user_type = class_basename(auth()->user());
        $transitionDef = Voucher::createTransitionDef('dispatched', 'collect');
        VoucherState::batchInsert($vouchers, $now, $user_id, $user_type, $transitionDef);

        // Check they're Recorded.
        $this->assertequals(100, VoucherState::where('to', 'recorded')->count());
    }
}
