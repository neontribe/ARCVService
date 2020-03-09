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
    protected function setUp()
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class, 'dispatched')->create();
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
        $this->assertInternalType('integer', $v->sponsor_id);

        // Add optional trader_id and check type.
        $v->trader_id = 1;
        $v->save();
        $this->assertInternalType('integer', $v->trader_id);
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
            'tst0123455',
            'tst0123456',
            'tst0123457'
        ];
        foreach ($goodCodes as $goodCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $goodCode
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            $voucher->applyTransition('dispatch');
        }

        // Mangled codes from bad input
        $badCodes = [
            'tst012 3455',
            'tst 0123456',
            'tst0123457'
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
        $v2 = factory(Voucher::class, 'dispatched')->create();
        $v2->applyTransition('collect');
        $v2->applyTransition('confirm');
        $v2->applyTransition('payout');
        // A couple of Payment Pending Vouchers.
        $v3 = factory(Voucher::class, 'dispatched', 2)->create();
        $v3[0]->applyTransition('collect');
        $v3[0]->applyTransition('confirm');
        $v3[1]->applyTransition('collect');
        $v3[1]->applyTransition('confirm');

        $this->assertCount(3, Voucher::confirmed()->get());
        $this->assertEquals([2,3,4], Voucher::confirmed()->pluck('id')->toArray());
    }

    /**
     * Here because I can't work out how to test the Stateable Trait well
     *
     * @test
     */
    public function testItCanCreateAValidTransitionDefinition()
    {
        $validTransDef = Voucher::createTransitionDef("ordered", "print");

        $this->assertEquals('print', $validTransDef->name);
        $this->assertEquals('ordered', $validTransDef->from);
        $this->assertNotNull('printed', $validTransDef->to);

        // Has an invalid transition name
        $this->assertNull(Voucher::createTransitionDef("ordered", "spacejam!"));

        // Has an invalid transition "from" state
        $this->assertNull(Voucher::createTransitionDef("kensington", "printed"));
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
        $input = [
            'voucher-start' => reset($vouchers)->code,
            'voucher-end' => end($vouchers)->code,
        ];
        $validRangeDef = Voucher::createRangeDefFromArray($input);

        $this->assertEquals($sponsor->id, $validRangeDef->sponsor_id);
        $this->assertInternalType('integer', $validRangeDef->sponsor_id);
        $this->assertEquals($sponsor->shortcode, $validRangeDef->shortcode);
        $this->assertEquals(10, $validRangeDef->start);
        $this->assertInternalType('integer', $validRangeDef->start);
        $this->assertEquals(1000, $validRangeDef->end);
        $this->assertInternalType('integer', $validRangeDef->end);

        // If you pass it a duff shortcode, it takes exception
        $input = [
            'voucher-start' => 'INV999998',
            'voucher-end' => 'INV999999',
        ];
        $this->expectException(ModelNotFoundException::class);
        Voucher::createRangeDefFromArray($input);
    }
}
