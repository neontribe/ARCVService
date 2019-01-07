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
use URL;


class PaymentPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $voucher;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        // Login to advance voucher
        $this->user = factory(User::class)->create();
        Auth::login($this->user);

        // Create a voucher and a trader with some info
        $this->voucher = factory(Voucher::class, 'requested')->create();
        $trader = factory(Trader::class, 'withnullable')->create();
        $this->voucher->code = 'TEST12345';
        $this->voucher->trader_id = $trader->id;
        $this->voucher->save();

        // Transition to PaymentPending
        $this->voucher->applyTransition('order');
        $this->voucher->applyTransition('print');
        $this->voucher->applyTransition('dispatch');
        $this->voucher->applyTransition('collect');
        $this->voucher->applyTransition('confirm');

        $stateToken = new StateToken();
        $stateToken->uuid = "a-real-uuid";
        $stateToken->save();

        $vs = $this->voucher->history->last();
        $vs->stateToken()->associate($stateToken);
        $vs->save();
    }

    /** @test */
    public function itShowsAnErrorWhenPaymentLinkBad()
    {
        // We can only reach the payment page when logged out
        Auth::logout();

        // A poorly formed uuid should show the page, but with an error message in place of the voucher table
        $this->visit(URL::route('store.payment-request.show', [ 'id' => 'a-made-up-uuid' ]))
            ->seeInElement('p[class="content-warning center"]', 'This payment request is invalid, or has expired.')
            ->dontSeeElement('table')
        ;
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
