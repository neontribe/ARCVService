<?php

namespace Tests\Unit\Models;

use Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Trader;
use App\Voucher;
use App\User;

class TraderModelTest extends TestCase
{

    use DatabaseMigrations;

    protected $trader;
    protected function setUp()
    {
        parent::setUp();
        $this->trader = factory(Trader::class, 'withnullable')->create();
    }

    public function testTraderIsCreatedWithExpectedAttributes()
    {
        $t = $this->trader;
        // Keeping it simple to make writing test suite less onerous.
        // The default error returned by asserts will be enough.
        $this->assertInstanceOf(Trader::class, $t);
        $this->assertNotNull($t->name);
        $this->assertNotNull($t->pic_url);
        $this->assertNotNull($t->market_id);
        $this->assertInternalType('integer', $t->market_id);
    }


    public function testSoftDeleteTrader()
    {
        $this->trader->delete();
        $this->assertCount(1, Trader::withTrashed()->get());
        $this->assertCount(0, Trader::all());
    }

    public function testTraderHasManyVouchers()
    {
        factory(Voucher::class, 10)->create([
            'trader_id' => $this->trader->id,
        ]);
         factory(Voucher::class, 2)->create([
            'trader_id' => $this->trader->id +1,
        ]);
        $this->assertCount(10, $this->trader->vouchers);
        $this->assertNotEquals($this->trader->vouchers, Voucher::all());
    }

    public function testTraderHasConfirmedVouchers()
    {
        $vouchers = factory(Voucher::class, 'requested', 3)->create([
            'trader_id' => $this->trader->id,
        ]);

        // Make a user and progress voucher states.
        $user = factory(User::class)->create();
        Auth::login($user);

        foreach ($vouchers as $v) {
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
            $v->applyTransition('allocate');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }
        $vouchers[0]->applyTransition('confirm');
        $vouchers[0]->applyTransition('payout');
        $vouchers[1]->applyTransition('confirm');
        $vouchers[2]->applyTransition('confirm');

        $confirmed_codes = [
            $vouchers[0]->code => $vouchers[0]->currentstate,
            $vouchers[1]->code => $vouchers[1]->currentstate,
            $vouchers[2]->code => $vouchers[2]->currentstate,
        ];
        $vc = $this->trader->vouchersConfirmed();
        $this->assertCount(3, $vc->get());
        $vc_code_states = $vc->pluck('currentstate', 'code')->toArray();
        $this->assertEquals($confirmed_codes, $vc_code_states);
    }

}
