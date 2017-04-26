<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Voucher;
use App\User;
use SM\SMException;
use Auth;
use Log;

class StatableVoucherTest extends TestCase
{

    use DatabaseMigrations, DatabaseTransactions;

    protected $voucher;
    protected function setUp()
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class)->create();
        Auth::login(factory(User::class)->create());
    }

    public function testAVoucherIsCreated()
    {
        $this->assertInstanceOf('App\Voucher', $this->voucher, "Voucher missing.");
    }

    public function testCreateVoucherStateMachine()
    {
        // Check there's an FSM for the model
        $this->assertInstanceOf('SM\StateMachine\StateMachine', $this->voucher->getStateMachine());
    }

    public function testGetVoucherState()
    {
        // Start state should be requested
        $this->assertEquals('requested', $this->voucher->currentstate);
    }

    public function testProgressVoucherState()
    {
        // Order some printed copies
        $this->voucher->applyTransition('order');

        // Should 1 state event, "ordered"
        $this->assertEquals('ordered', $this->voucher->currentstate);
        $this->assertEquals(1, $this->voucher->history()->count());

        // Register printed vouchers
        $this->voucher->applyTransition('print');

        // Should be 2 events, "ordered" and "printed".
        $this->assertEquals('printed', $this->voucher->currentstate);
        $this->assertEquals(2, $this->voucher->history()->count());
    }

    public function testTransitionAllowed()
    {
        // Can we progress to the next step? requested->ordered
        $this->assertTrue($this->voucher->transitionAllowed('order'));
        // Can we jump a few steps?
        $this->assertFalse($this->voucher->transitionAllowed('collect'));
    }

    public function testInvalidTransition()
    {
        $this->expectException(SMException::class);
        $this->voucher->state('collect');
    }

}
