<?php

namespace Tests;

use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;
use App\VoucherState;
use App\StateToken;
use App\Trader;
use URL;


class PaymentPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $voucher;

    protected function setUp()
    {
        parent::setUp();

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
        // A poorly formed uuid should show the page, but with an error message in place of the voucher table
        $this->visit(URL::route('store.payment-request.show', [ 'id' => 'a-made-up-uuid' ]))
            ->seeInElement('p[class="content-warning center"]', 'This payment request is invalid, or has expired.')
            ->dontSeeElement('table')
        ;
    }

    /** @test */
    public function itShowsPaymentRequestDetailsOnlyForValidPaymentUUIDs()
    {
        // A real uuid will display the data table
        $this->visit(URL::route('store.payment-request.show', [ 'id' => 'a-real-uuid' ]))
            ->seeElement('table')
            ->see('Voucher Code')
            ->see('Status')
            ->see('Date')
        ;
    }

    /** @test */
    public function itShowsPayButtonWhenOnlyPaymentIsUnpaid()
    {
        // A real uuid will display the data table
        $this->visit(URL::route('store.payment-request.show', [ 'id' => 'a-real-uuid' ]))
            ->seeElement('button[type="submit"]')
            ->see('Pay')
        ;
    }

    /** @test */
    public function itShowsPayemntDetailsOnlyWhenPaymentIsPaid()
    {
        // Transition the voucher to paid
        $this->voucher->applyTransition('payout');

        // We should no longer see the payment button, but see paid status
        $this->visit(URL::route('store.payment-request.show', [ 'id' => 'a-real-uuid' ]))
            ->dontSeeElement('button[type="submit"]')
            ->seeInelement('span[class="status paid"]', 'Paid')
        ;
    }

}
