<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use SM\SMException;
use App\Voucher;
use App\VoucherState;
use App\StateToken;
use App\User;
use Auth;

// We might move these out of Model tests - as they are really StateMachine tests.
class VoucherStateModelTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    protected $user;

    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
    }


    public function testProgressVoucherState()
    {

        // We need an auth's user to progress the voucher states.
        Auth::login($this->user);

        $voucher = factory(Voucher::class, 'requested')->create();

        // Order some printed copies
        $voucher->applyTransition('order');

        // Should 1 state event, "ordered"
        $this->assertEquals('ordered', $voucher->currentstate);
        $this->assertEquals(1, $voucher->history()->count());

        // Register printed vouchers
        $voucher->applyTransition('print');

        // Should be 2 events, "ordered" and "printed".
        $this->assertEquals('printed', $voucher->currentstate);
        $this->assertEquals(2, $voucher->history()->count());
    }

    public function testTransitionAllowed()
    {
        // We need an auth's user to progress the voucher states.
        Auth::login($this->user);

        $voucher = factory(Voucher::class, 'requested')->create();

        // Can we progress to the next step? requested->ordered
        $this->assertTrue($voucher->transitionAllowed('order'));
        // Can we jump a few steps?
        $this->assertFalse($voucher->transitionAllowed('collect'));
    }

    /**
     * @expectedException \SM\SMException
     */
    public function testInvalidTransition()
    {
        // We need an auth's user to progress the voucher states.
        Auth::login($this->user);

        $voucher = factory(Voucher::class, 'requested')->create();
        // This will throw exception $this->expectException() wasn't defined
        // But using the function annotation @expectedException works.
        $voucher->state('collect');
    }

    public function testAPrintedVoucherCanBeCollected()
    {
        Auth::login($this->user);
        $voucher = factory(Voucher::class, 'requested')->create();

        $voucher->applyTransition('order');
        $voucher->applyTransition('print');
        $voucher->applyTransition('collect');

        $this->assertEquals($voucher->currentstate, 'recorded');
    }

    public function testADispatchedVoucherCanBeCollected()
    {
        Auth::login($this->user);
        $voucher = factory(Voucher::class, 'requested')->create();

        $voucher->applyTransition('order');
        $voucher->applyTransition('print');
        $voucher->applyTransition('dispatch');
        $voucher->applyTransition('collect');

        $this->assertEquals($voucher->currentstate, 'recorded');
    }

    public function testARecordedVoucherCanBeRejectedBackToPrinted()
    {
        Auth::login($this->user);
        $voucher = factory(Voucher::class, 'requested')->create();

        $voucher->applyTransition('order');
        $voucher->applyTransition('print');
        $voucher->applyTransition('collect');
        $voucher->applyTransition('reject-to-printed');

        $this->assertEquals($voucher->currentstate, 'printed');
    }

    public function testARecordedVoucherCanBeRejectedBackToDispatched()
    {
        Auth::login($this->user);
        $voucher = factory(Voucher::class, 'requested')->create();

        $voucher->applyTransition('order');
        $voucher->applyTransition('print');
        $voucher->applyTransition('dispatch');
        $voucher->applyTransition('collect');
        $voucher->applyTransition('reject-to-dispatched');

        $this->assertEquals($voucher->currentstate, 'dispatched');
    }

    public function testAVoucherMayHaveAStateToken()
    {
        // Make a voucher
        Auth::login($this->user);
        $voucher = factory(Voucher::class, 'requested')->create();
        $voucher->applyTransition('order');
        $voucher->applyTransition('print');
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
}
