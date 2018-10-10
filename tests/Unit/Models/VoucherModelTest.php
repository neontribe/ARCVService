<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use SM\StateMachine\StateMachine;
use App\VoucherState;
use App\Voucher;
use App\Sponsor;
use App\Trader;
use App\User;
use Carbon\Carbon;
use Auth;

class VoucherModelTest extends TestCase
{
    use DatabaseMigrations;

    protected $voucher;
    protected function setUp()
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class, 'allocated')->create();
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

    public function testScopeConfirmedVouchers()
    {
        $user = factory(User::class)->create();
        Auth::login($user);

        // An allocated Voucher.
        $v1 = $this->voucher;
        // A reimbursed Voucher.
        $v2 = factory(Voucher::class, 'allocated')->create();
        $v2->applyTransition('collect');
        $v2->applyTransition('confirm');
        $v2->applyTransition('payout');
        // A couple of Payment Pending Vouchers.
        $v3 = factory(Voucher::class, 'allocated', 2)->create();
        $v3[0]->applyTransition('collect');
        $v3[0]->applyTransition('confirm');
        $v3[1]->applyTransition('collect');
        $v3[1]->applyTransition('confirm');

        $this->assertCount(3, Voucher::confirmed()->get());
        $this->assertEquals([2,3,4], Voucher::confirmed()->pluck('id')->toArray());
    }
}
