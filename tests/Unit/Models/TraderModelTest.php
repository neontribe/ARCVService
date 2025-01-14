<?php

namespace Tests\Unit\Models;

use App\Market;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraderModelTest extends TestCase
{
    use RefreshDatabase;

    protected $trader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trader = factory(Trader::class)->state('withnullable')->create();
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
        $this->assertIsInt($t->market_id);
        $this->assertNull($t->disabled_at);
    }

    public function testSoftDeleteTrader()
    {
        $this->trader->delete();
        $this->assertCount(1, Trader::withTrashed()->get());
        $this->assertCount(0, Trader::all());
    }

    public function testItCanBeDisabledAndEnabled()
    {
        $this->trader->disable();
        $this->trader->refresh();
        $this->assertInstanceOf(Carbon::class, $this->trader->disabled_at);

        $this->trader->enable();
        $this->trader->refresh();
        $this->assertNull($this->trader->disabled_at);
    }

    public function testTraderBelongsToMarket()
    {
        $this->assertInstanceOf(Market::class, $this->trader->market);
    }

    public function testTraderHasManyVouchers()
    {
        factory(Voucher::class, 10)->create([
            'trader_id' => $this->trader->id,
        ]);
        factory(Voucher::class, 2)->create([
            'trader_id' => $this->trader->id + 1,
        ]);
        $this->assertCount(10, $this->trader->vouchers);
        $this->assertNotEquals($this->trader->vouchers, Voucher::all());
    }

    public function testTraderHasConfirmedVouchers()
    {
        $vouchers = factory(Voucher::class, 3)->state('printed')->create([
            'trader_id' => $this->trader->id,
        ]);

        // Make a user and progress voucher states.
        $user = factory(User::class)->create();
        Auth::login($user);

        foreach ($vouchers as $v) {
            $v->applyTransition('dispatch');
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

    public function testTraderHasVouchersWithStatus()
    {
        $vouchers = factory(Voucher::class, 6)->state('printed')->create([
            'trader_id' => $this->trader->id,
        ]);

        // Make a user and progress voucher states.
        $user = factory(User::class)->create();
        Auth::login($user);

        foreach ($vouchers as $v) {
            $v->applyTransition('dispatch');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }
        $vouchers[0]->applyTransition('confirm');
        $vouchers[0]->applyTransition('payout');
        $vouchers[1]->applyTransition('confirm');
        $vouchers[2]->applyTransition('confirm');

        // 6 vouchers. 1x reimbursed ($vouchers[0]), 2x payment_pending, 3x recorded.
        $reference_codes = [
            $vouchers[1]->code => $vouchers[1]->currentstate,
            $vouchers[2]->code => $vouchers[2]->currentstate,
            $vouchers[3]->code => $vouchers[3]->currentstate,
            $vouchers[4]->code => $vouchers[4]->currentstate,
            $vouchers[5]->code => $vouchers[5]->currentstate,
        ];

        // 2 Vouchers in payment_pending state
        $vc = $this->trader->vouchersWithStatus('unpaid');
        $this->assertCount(2, $vc);

        // and they're the expected ones.
        $code_states = $vc->pluck('currentstate', 'code')->toArray();
        foreach ($code_states as $code => $state) {
            $this->assertEquals($reference_codes[$code], $state);
        }

        // 3 Vouchers in recorded
        $vc = $this->trader->vouchersWithStatus('unconfirmed');
        $this->assertCount(3, $vc);

        // and they're the expected ones.
        $code_states = $vc->pluck('currentstate', 'code')->toArray();
        foreach ($code_states as $code => $state) {
            $this->assertEquals($reference_codes[$code], $state);
        }
    }
}
