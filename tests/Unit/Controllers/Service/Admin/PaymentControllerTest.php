<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\StateToken;
use App\Trader;
use App\VoucherState;
use App\User;
use App\Voucher;
use App\Sponsor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;


class PaymentControllerTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $admin_user;
    protected $trader;
    protected $vouchers;


    public function setUp(): void
    {
        parent::setUp();
        // Create Admin
        $this->admin_user = factory(AdminUser::class)->create();

        // Create a Trader
        $this->trader = factory(Trader::class)->create();

    }

    /** @test */
    public function testItReturnsASpecificPaymentRequest()

    {
        //Create a token to pass to the route
        //Create some vouchers, give them a sponsor (as otherwise it migght error)
        // and progress them to payment_pending to get a UUID

        $token = factory(StateToken::class)->create();
        $s = factory(Sponsor::class)->create();
        // Make a pile of vouchers that are payment_pending
        $this->vouchers = factory(Voucher::class, 5)->state('printed')->create();
        foreach ($this->vouchers as $k => $voucher) {
            $voucher->code = 'RVNT' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $voucher->sponsor_id = $s->id;
            $voucher->trader_id = $this->trader->id;
            // Progress to dispatched.
            $voucher->applyTransition('dispatch');
            $voucher->applyTransition('collect');
            $voucher->applyTransition('confirm');

            $voucherState = $voucher->paymentPendedOn()->first();
            $voucherState->state_token_id = $token->id;
            $voucherState->voucher_id = $voucher->id;
            $voucherState->save();
            $voucher->save();
        }
        $data = $token->uuid;

        //pass the UUID to the route
        $route = route('admin.payment-request.show',['paymentUuid'=>$data]);

        $this->actingAs($this->admin_user, 'admin')
            ->get($route)
            ->assertResponseStatus(200);

        foreach($this->vouchers as $voucher){
            $this->see($voucher->code);
        }
        //TODO also test that I cannot see a different UUID in here
    }
//    /** @test */
//    public function testItUpdatesASpecificPaymentRequest()
//
//
//    {
//        //make a set of vouchers that are payment pending
//
//        //give them a Uuid
//
//        //check the voucher states
//
//        //push them through the transistion
//
//        //check the states again
//
//    }

}
