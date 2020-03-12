<?php

namespace Tests;

use App\Delivery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class DeliveryModelTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Delivery delivery */
    protected $delivery;
    protected function setUp()
    {
        parent::setUp();
        // create a blank factory
        $this->delivery = factory(Delivery::class)->create();
    }

    /** @test */
    public function testDeliveryIsCreatedWithExpectedAttributes()
    {
        $d = $this->delivery;
        $this->assertInstanceOf(Delivery::class, $d);
        $this->assertInternalType('integer', $d->centre_id);
        $this->assertNotNull($d->dispatched_at);
        // a blank delivery needn't have vouchers.
        $this->assertEmpty($d->vouchers);
    }

    /** @test */
    public function testPopulatedDeliveryIsCreatedWithExpectedAttributes()
    {
        $centre = factory('App\Centre')->create();

        // create three vouchers and transition to collected.
        $vs = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function ($v) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->applyTransition('dispatch');
                $v->delivery()->associate($this->delivery);
                $v->save();
            });

        //just to check we have the expected number of vouchers before continuing
        $this->assertEquals($vs->count(), $this->delivery->vouchers()->count());

        $dispatchedBundle = $this->delivery;

        $dispatchedBundle->dispatched_at = Carbon::now()->startOfDay();
        $dispatchedBundle->centre = $centre->id;

        $this->assertInternalType('integer', $dispatchedBundle->centre_id);
        $this->assertInstanceOf(Carbon::class, $dispatchedBundle->dispatched_at);
        $this->assertNotEmpty($dispatchedBundle->vouchers);
    }

    /** @test */
    public function testDeliveryCanHaveManyVouchers()
    {
        // Create three vouchers and transition to printed.
        $vs = factory('App\Voucher', 'requested', 3)
            ->create()
            ->each(function ($v) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->applyTransition('dispatch');
                $v->delivery()->associate($this->delivery);
                $v->save();
            });
        $this->assertEquals($vs->count(), $this->delivery->vouchers()->count());
    }
}
