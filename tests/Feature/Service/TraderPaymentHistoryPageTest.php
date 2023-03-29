<?php

namespace Tests\Feature\Service;

use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\AdminUser;
use Tests\StoreTestCase;

class TraderPaymentHistoryPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    private $adminUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();

//        $this->traderHistoryRoute = route('admin.trader-payment-history.show');

        // Create a voucher and a trader with some info
        // And a user otherwise transition rightly breaks integrity constraints
        $this->voucher = factory(Voucher::class)->state('printed')->create();
        $this->trader = factory(Trader::class)->state('withnullable')->create();
        $user = factory(User::class)->create();
        $this->voucher->code = 'TEST12345';
        $this->voucher->trader_id = $this->trader->id;
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

    /**
     * @test
     */
    public function itShowsATableWithHeaders()
    {
        $route = route('admin.trader-payment-history.show', ['trader' => $this->trader->id]);

        $this->actingAs($this->adminUser, 'admin')
            ->get($route)
            ->assertResponseOk()
            ->seeInElement('h1', 'Payment History')
            ->seeInElement('th', 'Request Date')
            ->seeInElement('th', 'Vouchers')
            ->seeInElement('th', 'Total');
        ;
    }
}

