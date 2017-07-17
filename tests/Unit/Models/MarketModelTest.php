<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Market;

class MarketModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $market;
    protected function setUp()
    {
        parent::setUp();
        $this->market = factory(Market::class)->create();
    }

    public function testMarketIsCreatedWithExpectedAttributes()
    {
        $m = $this->market;
        // Keeping it simple to make writing test suite less onerous.
        // The default error returned by asserts will be enough.
        $this->assertInstanceOf(Market::class, $m);
        $this->assertNotNull($m->name);
        $this->assertNotNull($m->location);
        $this->assertNotNull($m->payment_message);
        $this->assertNotNull($m->sponsor_id);
        $this->assertInternalType('integer', $m->sponsor_id);
    }


    public function testSoftDeleteMarket()
    {
        $this->market->delete();
        $this->assertCount(1, Market::withTrashed()->get());
        $this->assertCount(0, Market::all());
    }
}
