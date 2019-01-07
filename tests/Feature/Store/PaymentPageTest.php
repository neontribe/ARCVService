<?php

namespace Tests;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;
use App\VoucherState;
use App\StateToken;
use App\User;
use App\Trader;
use Auth;

class PaymentPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    public function setUp()
    protected function setUp()
    {
        parent::setUp();
        // Login to advance voucher
        $this->user = factory(User::class)->create();
        Auth::login($this->user);

        // Create a voucher and a trader with some info
        $voucher = factory(Voucher::class, 'requested')->create();
        $trader = factory(Trader::class, 'withnullable')->create();
        $voucher->code = 'TEST12345';
        $voucher->trader_id = $trader->id;
        $voucher->save();

        // Transition to PaymentPending
        $voucher->applyTransition('order');
        $voucher->applyTransition('print');
        $voucher->applyTransition('dispatch');
        $voucher->applyTransition('collect');
        $voucher->applyTransition('confirm');

        $stateToken = new StateToken();
        $stateToken->uuid = "a-real-uuid";
        $stateToken->save();

        $vs = $voucher->history->last();
        $vs->stateToken()->associate($stateToken);
        $vs->save();
    }

    /** @test */
    public function itShowsAnErrorWhenPaymentLinkBad()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    /** @test */
    public function itShowsPaymentRequestDetailsOnlyForValidPaymentUUIDs()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    /** @test */
    public function itShowsPayButtonWhenOnlyPaymentIsUnpaid()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    /** @test */
    public function itShowsPayemntDetailsOnlyWhenPaymentIsPaid()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

}
