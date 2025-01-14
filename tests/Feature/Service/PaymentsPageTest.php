<?php

namespace Tests\Feature\Service;

use App\User;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\AdminUser;
use App\Voucher;
use App\StateToken;
use App\Trader;
use URL;

class PaymentsPageTest extends StoreTestCase
{
    //All these are copied and adapted from old store payment page tests that were at Feature\Store and now defunct.
    use RefreshDatabase;

    protected $voucher;

    protected function setUp(): void
    {
        parent::setUp();
        // Create Admin as this is all in auth now
        $this->admin_user = factory(AdminUser::class)->create();

        $this->paymentsRoute = route('admin.payments.index');

        // Create a voucher and a trader with some info
        // And a user otherwise transition rightly breaks integrity constraints
        $this->voucher = factory(Voucher::class)->state('printed')->create();
        $trader = factory(Trader::class)->state('withnullable')->create();
        $user = factory(User::class)->create();
        $this->voucher->code = 'TEST12345';
        $this->voucher->trader_id = $trader->id;
        $this->voucher->save();

        // Transition to PaymentPending
        $this->voucher->applyTransition('dispatch');
        $this->voucher->applyTransition('collect');
        $this->voucher->applyTransition('confirm');

        $stateToken = new StateToken();
        $stateToken->uuid = "a-real-uuid";
        $stateToken->save();

        $vs = $this->voucher->history->last();
        $vs->stateToken()->associate($stateToken);
        $vs->user_id = $user->id;
        $vs->save();
    }
    /** @test */
    public function itShowsATableWithHeaders()
    {
        $this->actingAs($this->admin_user, 'admin')
            ->visit($this->paymentsRoute)
            ->assertResponseOk()
            ->seeInElement('h1', 'Payment Requests')
            ->seeInElement('th', 'Name')
            ->seeInElement('th', 'Market')
            ->seeInElement('th', 'Area')
            ->seeInElement('th', 'Requested By')
            ->seeInElement('th', 'Voucher Area')
            ->seeInElement('th', 'Total')
        ;
    }

    /** @test */
    public function itShowsOutstandingPaymentsInSidebar()
    {
        $this->actingAs($this->admin_user, 'admin')
            ->visit($this->paymentsRoute)
            ->assertResponseOk()
            ->seeInElement('a.payments','Payment Requests')
        ;

    }

    /** @test */
    public function itShowsAnErrorWhenPaymentLinkBad()
    {
        $this->actingAs($this->admin_user, 'admin')
        // A poorly formed uuid should show the page, but with an error message in place of the voucher table
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-made-up-uuid' ]))
            ->seeInElement('p[class="content-warning center"]', 'This payment request is invalid, or has expired.')
            ->dontSeeElement('table')
        ;
    }

    /** @test */
    public function itShowsPaymentRequestDetailsOnlyForValidPaymentUUIDs()
    {
        // A real uuid will display the data table
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-real-uuid' ]))
            ->seeElement('table')
            ->seeInElement('th', 'Voucher Code')
            ->seeInElement('th', 'Status')
            ->seeInElement('th', 'Date')
        ;

        // A made up uuid will not display the data table
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-made-up-uuid' ]))
            ->dontseeElement('table')
            ->dontseeInElement('th', 'Voucher Code')
            ->dontseeInElement('th', 'Status')
            ->dontseeInElement('th', 'Date')
            ->see('This payment request is invalid, or has expired.')
        ;
    }

    /** @test */
    public function itShowsPayButtonWhenOnlyPaymentIsUnpaid()
    {
        // A real unpaid uuid will display the pay button
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-real-uuid' ]))
            ->seeInElement('button[class="btn link-button"]', 'Pay <b>1</b> Vouchers')
        ;

        // Transition the voucher to paid
        $this->voucher->applyTransition('payout');

        // A real paid uuid will not display the pay button
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-real-uuid' ]))
            ->dontSeeInElement('button[class="btn link-button"]', 'Pay <b>1</b> Vouchers')
        ;
    }

    /** @test */
    public function itShowsTheCorrectVoucherStatus()
    {
        // A made up uuid will display no statuses
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-made-up-uuid' ]))
            ->dontSeeInElement('span[class="status requested"]', 'Requested')
            ->dontSeeInElement('span[class="status paid"]', 'Paid')
        ;

        // A real unpaid uuid will display requested status
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-real-uuid' ]))
            ->seeInElement('span[class="link-button link-button-small requested"]', 'Requested')
        ;

        // Transition the voucher to paid
        $this->voucher->applyTransition('payout');

        // A real paid uuid will display paid status
        $this->actingAs($this->admin_user, 'admin')
            ->visit(URL::route('admin.payment-request.show', [ 'paymentUuid' => 'a-real-uuid' ]))
            ->seeInElement('span[class="status paid"]', 'Paid')
        ;
    }

}

