<?php

namespace Tests\Unit\Controllers\Service\Admin;


use App\StateToken;
use App\Trader;
use App\AdminUser;
use Carbon\Carbon;
use Illuminate\View\View;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
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
        $this->vouchers = factory(Voucher::class, 10)->state('printed')->create();
    }


    /** @test */
    public function testItReturnsThePast7DaysPayments()
    {

    }

    /** @test */
    public function testItReturnsASpecificPaymentRequest()
    {

    }

}
