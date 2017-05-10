<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use SM\StateMachine\StateMachine;
use App\Voucher;
use App\Sponsor;
use App\Trader;

class VoucherModelTest extends TestCase
{

    use DatabaseMigrations;

    protected $voucher;
    protected function setUp()
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class)->create();
    }

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

    public function testCreateVoucherStateMachine()
    {
        // Check there's an FSM for the model
        $this->assertInstanceOf(StateMachine::class, $this->voucher->getStateMachine());
    }

    public function testSoftDeleteVoucher()
    {
        $this->voucher->delete();
        $this->assertCount(1, Voucher::withTrashed()->get());
        $this->assertCount(0, Voucher::all());
    }

    public function testVoucherBelongsToSponsor()
    {
        $this->assertInstanceOf(Sponsor::class, $this->voucher->sponsor);
    }

    public function testVoucherCanBelongToTrader()
    {
        // The voucher factory creates a sponsor because it's required.
        // But not a Trader which is nullable.
        $voucher = factory(Voucher::class)->create([
            'trader_id' => factory(Trader::class)->create()->id,
        ]);
        $this->assertInstanceOf(Trader::class, $voucher->trader);
    }

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

    public function testFindVouchersByCodes()
    {
        //Todo
    }
}
