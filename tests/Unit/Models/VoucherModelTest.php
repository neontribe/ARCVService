<?php

namespace Tests\Unit\Models;

use App\Delivery;
use App\Sponsor;
use App\Trader;
use App\User;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use SM\StateMachine\StateMachine;
use Tests\TestCase;

class VoucherModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $voucher;
    protected function setUp(): void
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class)->state('dispatched')->create();
    }

    /** @test */
    public function testAVoucherIsCreatedWithExpectedAttributes()
    {
        $v = $this->voucher;
        // Keeping it simple to make writing test suite less onerous.
        // The default error returned by asserts will be enough.
        $this->assertInstanceOf(Voucher::class, $v);
        $this->assertNotNull($v->code);
        $this->assertTrue(in_array($v->currentstate, config('state-machine.Voucher.states')));
        $this->assertNotNull($v->sponsor_id);
        $this->assertIsInt($v->sponsor_id);

        // Add optional trader_id and check type.
        $v->trader_id = 1;
        $v->save();
        $this->assertIsInt($v->trader_id);
    }

    /** @test */
    public function testCreateVoucherStateMachine()
    {
        // Check there's an FSM for the model
        $this->assertInstanceOf(StateMachine::class, $this->voucher->getStateMachine());
    }

    /** @test */
    public function testSoftDeleteVoucher()
    {
        $this->voucher->delete();
        $this->assertCount(1, Voucher::withTrashed()->get());
        $this->assertCount(0, Voucher::all());
    }

    /** @test */
    public function testVoucherBelongsToSponsor()
    {
        $this->assertInstanceOf(Sponsor::class, $this->voucher->sponsor);
    }

    /** @test */
    public function testVoucherCanBelongToTrader()
    {
        // The voucher factory creates a sponsor because it's required.
        // But not a Trader which is nullable.
        $voucher = factory(Voucher::class)->create([
            'trader_id' => factory(Trader::class)->create()->id,
        ]);
        $this->assertInstanceOf(Trader::class, $voucher->trader);
    }

    /** @test */
    public function testVoucherCanBelongToDelivery()
    {
        $voucher = factory(Voucher::class)->create([
            'delivery_id' => factory(Delivery::class)->create()->id,
        ]);
        $this->assertInstanceOf(Delivery::class, $voucher->delivery);
    }

    /** @test */
    public function testFindVoucherByCode()
    {
        $a = factory(Voucher::class)->create([
            'code' => 'aaaaa',
        ]);
        $b = factory(Voucher::class)->create([
            'code' => 'bbbbb',
        ]);

        // Call fresh() to get the up to date object.
        // We should investigate seems that the orig has int ids but fresh is strings.
        // Is this a feature of sqlite? or factory?
        $this->assertEquals($a->fresh(), Voucher::findByCode('aaaaa'));
        $this->assertNotEquals($b->fresh(), Voucher::findByCode('aaaaa'));
    }

    /** @test */
    public function testGetVoucherPendedOnDay()
    {
        $v = $this->voucher;
        $user = factory(User::class)->create();
        Auth::login($user);
        $v->applyTransition('collect');
        $v->applyTransition('confirm');
        $this->assertInstanceOf(VoucherState::class, $v->paymentPendedOn);
        $this->assertEquals(
            Carbon::now()->format('Ymd'),
            $v->paymentPendedOn->created_at->format('Ymd')
        );
    }

    /** @test */
    public function testGetVoucherRecordedOnDay()
    {
        $v = $this->voucher;
        $user = factory(User::class)->create();
        Auth::login($user);
        $v->applyTransition('collect');
        $this->assertInstanceOf(VoucherState::class, $v->recordedOn);
        $this->assertEquals(
            Carbon::now()->format('Ymd'),
            $v->recordedOn->created_at->format('Ymd')
        );
    }

    /** @test */
    public function testGetVoucherReimbursedOnDay()
    {
        $v = $this->voucher;
        $user = factory(User::class)->create();
        Auth::login($user);
        $v->applyTransition('collect');
        $v->applyTransition('confirm');
        $v->applyTransition('payout');
        $this->assertInstanceOf(VoucherState::class, $v->reimbursedOn);
        $this->assertEquals(
            Carbon::now()->format('Ymd'),
            $v->reimbursedOn->created_at->format('Ymd')
        );
    }

    /** @test */
    public function testCleanVouchers()
    {
        $user = factory(User::class)->create();
        Auth::login($user);

        // Create a voucher set ready to go
        $goodCodes = [
            'TST0123455',
            'TST0123456',
            'TST0123457'
        ];
        foreach ($goodCodes as $goodCode) {
            $voucher = factory(Voucher::class)->state('printed')->create([
                'code' => $goodCode
            ]);
            $voucher->applyTransition('dispatch');
        }

        // Mangled codes from bad input
        $badCodes = [
            'TST012 3455',
            'TST 0123456',
            'TST0123457'
        ];
        // Clean 'em up!
        $cleanCodes = Voucher::cleanCodes($badCodes);

        // Find 'em in the database
        $vouchers = Voucher::findByCodes($cleanCodes);
        $this->assertEquals(count($badCodes), $vouchers->count());
    }

    /** @test */
    public function testScopeConfirmedVouchers()
    {
        $user = factory(User::class)->create();
        Auth::login($user);

        // A dispatched Voucher.
        $v1 = $this->voucher;
        // A reimbursed Voucher.
        $v2 = factory(Voucher::class)->state('dispatched')->create();
        $v2->applyTransition('collect');
        $v2->applyTransition('confirm');
        $v2->applyTransition('payout');
        // A couple of Payment Pending Vouchers.
        $v3 = factory(Voucher::class, 2)->state('dispatched')->create();
        $v3[0]->applyTransition('collect');
        $v3[0]->applyTransition('confirm');
        $v3[1]->applyTransition('collect');
        $v3[1]->applyTransition('confirm');

        $ids = Voucher::confirmed()->orderBy('id')->get()->pluck('id')->toArray();
        $this->assertEquals([2,3,4], $ids);
    }

    /**
     * Here because I can't work out how to test the Stateable Trait well
     *
     * @test
     */
    public function testItCanCreateAValidTransitionDefinition()
    {
        $validTransDef = Voucher::createTransitionDef("printed", "dispatch");

        $this->assertEquals('dispatch', $validTransDef->name);
        $this->assertEquals('printed', $validTransDef->from);
        $this->assertNotNull('dispatched', $validTransDef->to);

        // Has an invalid transition name
        $this->assertNull(Voucher::createTransitionDef("printed", "spacejam!"));

        // Has an invalid transition "from" state
        $this->assertNull(Voucher::createTransitionDef("kensington", "dispatched"));
    }

    /** @test */
    public function testItCanCreateARangeDef()
    {
        // Make some vouchers
        $sponsor = factory(Sponsor::class)->create([
            'shortcode' => "TST",
        ]);
        $vouchers[] = factory(Voucher::class)->create([
            'code' => 'TST0010',
            'sponsor_id' => $sponsor->id,
        ]);
        $vouchers[] = factory(Voucher::class)->create([
            'code' => 'TST1000',
            'sponsor_id' => $sponsor->id,
        ]);

        // Make some input
        $validRangeDef = Voucher::createRangeDefFromVoucherCodes(reset($vouchers)->code, end($vouchers)->code);

        $this->assertEquals($sponsor->id, $validRangeDef->sponsor_id);
        $this->assertIsInt($validRangeDef->sponsor_id);
        $this->assertEquals($sponsor->shortcode, $validRangeDef->shortcode);
        $this->assertEquals(10, $validRangeDef->start);
        $this->assertIsInt($validRangeDef->start);
        $this->assertEquals(1000, $validRangeDef->end);
        $this->assertIsInt($validRangeDef->end);

        // If you pass it a duff shortcode, it takes exception
        $this->expectException(ModelNotFoundException::class);
        Voucher::createRangeDefFromVoucherCodes('INV999998', 'INV999999');
    }

    /** @test */
    public function testItCanFindASupersetRangeFromARangeSet()
    {
        $rangeCodes = [
            'TST0101',
            'TST0102',
            'TST0103',
            'TST0104',
            'TST0105',

            'TST0201',
            'TST0202',
            'TST0203',
            'TST0204',
            'TST0205',

            'TST0301',
            'TST0302',
            'TST0303',
            'TST0304',
            'TST0305',
        ];

        $sponsor = factory(Sponsor::class)->create(
            ['shortcode' => 'TST']
        );

        foreach ($rangeCodes as $rangeCode) {
            $voucher = factory(Voucher::class)->state('printed')->create([
                'code' => $rangeCode,
                'sponsor_id' => $sponsor->id,
            ]);
        }

        $ranges = [
            Voucher::createRangeDefFromVoucherCodes('TST0101', 'TST0105'),
            Voucher::createRangeDefFromVoucherCodes('TST0201', 'TST0205'),
            Voucher::createRangeDefFromVoucherCodes('TST0301', 'TST0305'),
        ];

        $inBoundsRange = Voucher::createRangeDefFromVoucherCodes('TST0202', 'TST0204');

        $voucher = new Voucher();
        // Invoke the private method to check a good range
        $range = $this->invokeMethod($voucher, 'getContainingRange', [
            $inBoundsRange->start,
            $inBoundsRange->end,
            $ranges
        ]);

        $this->assertEquals(201, $range->start);
        $this->assertEquals(205, $range->end);

        // Send a bad rang through
        $range = $this->invokeMethod($voucher, 'getContainingRange', [
            202,
            209,
            $ranges
        ]);
        $this->assertNull($range);
    }

    // Cheeky method for accessing private methods.
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        try {
            $reflection = new \ReflectionClass(get_class($object));
            $method = $reflection->getMethod($methodName);
            $method->setAccessible(true);
            return $method->invokeArgs($object, $parameters);
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}
