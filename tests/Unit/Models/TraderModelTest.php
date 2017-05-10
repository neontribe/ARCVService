<?php

namespace Tests\Unit\Models;

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
}
