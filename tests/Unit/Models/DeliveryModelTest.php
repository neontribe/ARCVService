<?php

namespace Tests\Unit\Models;

use App\Centre;
use App\Delivery;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryModelTest extends TestCase
{
    use RefreshDatabase;

    /** @var Delivery delivery */
    protected $delivery;
    protected function setUp(): void
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
        $this->assertIsInt($d->centre_id);
        $this->assertNotNull($d->dispatched_at);
        $this->assertInstanceOf(Carbon::class, $d->dispatched_at);
        // a blank delivery needn't have vouchers, nor range
        $this->assertEmpty($d->vouchers);
        $this->assertEmpty($d->range);
    }

    /** @test */
    public function testPopulatedDeliveryIsCreatedWithExpectedAttributes()
    {
        $centre = factory(Centre::class)->create();

        // create three vouchers and transition to collected.
        $vs = factory(Voucher::class, 3)->state('printed')
            ->create()
            ->each(function ($v) {
                $v->applyTransition('dispatch');
                $v->delivery()->associate($this->delivery);
                $v->save();
            });

        //just to check we have the expected number of vouchers before continuing
        $this->assertEquals($vs->count(), $this->delivery->vouchers()->count());

        $dispatchedBundle = $this->delivery;

        $dispatchedBundle->dispatched_at = Carbon::now()->startOfDay();
        $dispatchedBundle->centre_id = $centre->id;

        $this->assertIsInt($dispatchedBundle->centre_id);
        $this->assertInstanceOf(Carbon::class, $dispatchedBundle->dispatched_at);
        $this->assertNotEmpty($dispatchedBundle->vouchers);
    }

    /** @test */
    public function testDeliveryCanHaveManyVouchers()
    {
        // Create three vouchers and transition to dipatched.
        $vs = factory(Voucher::class, 3)->state('printed')
            ->create()
            ->each(function ($v) {
                $v->applyTransition('dispatch');
                $v->delivery()->associate($this->delivery);
                $v->save();
            });
        $this->assertEquals($vs->count(), $this->delivery->vouchers()->count());
    }

    /** @test */
    public function testDeliveryBelongsToACentre()
    {
        $this->assertInstanceOf('App\Centre', $this->delivery->centre);
    }

    /** @test */
    public function testOrderDeliveriesByField()
    {
        factory(Delivery::class)->create([
            'centre_id' => factory(Centre::class)->create(['name' => 'Adriatic Centre'])->id,
            'dispatched_at' => Carbon::yesterday(),
            'range' => 'ABC101-ABC200',
        ]);
        factory(Delivery::class)->create([
            'centre_id' => factory(Centre::class)->create(['name' => 'Zanzibar Centre'])->id,
            'dispatched_at' => Carbon::tomorrow(),
            'range' => 'XYZ101-XYZ200',
        ]);
        factory(Delivery::class)->create([
            'centre_id' => factory(Centre::class)->create(['name' => 'Medditeranean Centre'])->id,
            'dispatched_at' => Carbon::now(),
            'range' => 'ABC201-ABC300',
        ]);

        // Exclude the setUp delivery, we don't know the centre name.
        $this->delivery->delete();

        $noOrder =  Delivery::pluck('id')->toArray();

        // Ascending by default, test with individual scopes
        $defaultCentreOrder = Delivery::orderByCentre()->pluck('id')->toArray();
        $defaultDispatchOrder = Delivery::orderByDispatchDate()->pluck('id')->toArray();
        $defaultRangeOrder = Delivery::orderByRange()->pluck('id')->toArray();

        // Test the scopes including a direction through the orderByField wrapper.
        $ascCentreOrder = Delivery::orderByField(['orderBy' => 'centre', 'direction' => 'asc'])
            ->pluck('id')->toArray()
        ;
        $descCentreOrder = Delivery::orderByField(['orderBy' => 'centre', 'direction' => 'desc'])
            ->pluck('id')->toArray()
        ;
        $ascDispatchOrder = Delivery::orderByField(['orderBy' => 'dispatchDate', 'direction' => 'asc'])
            ->pluck('id')->toArray()
        ;
        $descDispatchOrder = Delivery::orderByField(['orderBy' => 'dispatchDate', 'direction' => 'desc'])
            ->pluck('id')->toArray()
        ;
        $ascRangeOrder = Delivery::orderByField(['orderBy' => 'range', 'direction' => 'asc'])
            ->pluck('id')->toArray()
        ;
        $descRangeOrder = Delivery::orderByField(['orderBy' => 'range', 'direction' => 'desc'])
            ->pluck('id')->toArray()
        ;

        $this->assertEquals([2,3,4], $noOrder);
        $this->assertEquals($defaultCentreOrder, $ascCentreOrder);
        $this->assertEquals($defaultDispatchOrder, $ascDispatchOrder);
        $this->assertEquals($defaultRangeOrder, $ascRangeOrder);
        $this->assertEquals([2,4,3], $ascCentreOrder);
        $this->assertEquals([3,4,2], $descCentreOrder);
        $this->assertEquals([2,4,3], $ascDispatchOrder);
        $this->assertEquals([3,4,2], $descDispatchOrder);
        $this->assertEquals([2,4,3], $ascRangeOrder);
        $this->assertEquals([3,4,2], $descRangeOrder);
    }
}
