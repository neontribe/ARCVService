<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Sponsor;
use App\User;
use App\Voucher;
use Auth;
use Tests\MysqlStoreTestCase;

class VoucherControllerMysqlTest extends MysqlStoreTestCase
{
    protected $user;
    protected $sponsor;
    protected $rangeCodes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeCodes = [
            'TST0101',
            'TST0102',
            'TST0103',
            'TST0104',
            'TST0105',
            'TST0106',

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

        // Make a sponsor to match
        $this->sponsor = factory(Sponsor::class)->create(
            ['shortcode' => 'TST']
        );

        $this->user = factory(User::class)->create();

        Auth::login($this->user);

        foreach ($this->rangeCodes as $rangeCode) {
            $voucher = factory(Voucher::class, 'printed')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
            $voucher->applyTransition('dispatch');
        }

        Auth::logout();
    }

    /** @test */
    public function testItCanVoidVoucherCodes()
    {
        // The post data
        $data = [
            'voucher-start' => 'TST0102',
            'voucher-end' => 'TST0104',
            'transition' => 'void'
        ];

        // Set some routes
        $formRoute = route('admin.vouchers.void');
        $requestRoute = route('admin.vouchers.retirebatch');
        $successRoute = route('admin.vouchers.index');

        // Set the message to look for
        $msg = trans('service.messages.vouchers_batchretiretransition.success', [
            'transition_to' => 'retired',
            'success_codes' => 'TST0102 TST0103 TST0104',
            'fail_code_details' => '',
        ]);

        // Make the patch
        $this->actingAs($this->user, 'admin')
            ->visit($formRoute)
            ->patch($requestRoute, $data)
            ->followRedirects()
            ->seePageIs($successRoute)
            ->see($msg)
        ;

        // fetch those back.
        $vouchers = Voucher::where('currentstate', 'retired')
            ->with('history')
            ->get()
        ;

        // Check there are 3.
        $this->assertCount(3, $vouchers);

        $vouchers->each(function ($v) {
            $this->assertEquals(1, $v->history->where('to', 'voided')->count());
            $this->assertEquals(1, $v->history->where('to', 'retired')->count());
        });
    }

    /** @test */
    public function testItCanVoidVoucherCodesInABatchWithNonVoidables()
    {
        // The post data
        $data = [
            'voucher-start' => 'TST0102',
            'voucher-end' => 'TST0106',
            'transition' => 'void'
        ];

        // Set 0104 and 0106 back to printed
        $setToPrinted = Voucher::whereIn('code', ['TST0104', 'TST0106'])->get();
        foreach ($setToPrinted as $v) {
            $v->applyTransition('collect');
            $v->applyTransition('reject-to-printed');
        }

        // Set some routes
        $formRoute = route('admin.vouchers.void');
        $requestRoute = route('admin.vouchers.retirebatch');
        $successRoute = route('admin.vouchers.index');

        // Set the message to look for
        $msg = trans('service.messages.vouchers_batchretiretransition.success', [
            'transition_to' => 'retired',
            'success_codes' => 'TST0102 TST0103 TST0105',
            'fail_code_details' => 'TST0104 TST0106',
        ]);

        // Make the patch
        $this->actingAs($this->user, 'admin')
            ->visit($formRoute)
            ->patch($requestRoute, $data)
            ->followRedirects()
            ->seePageIs($successRoute)
            ->see($msg)
        ;

        // fetch those back.
        $vouchers = Voucher::where('currentstate', 'retired')
            ->with('history')
            ->get()
        ;

        // Check there are 3.
        $this->assertCount(3, $vouchers);

        $vouchers->each(function ($v) {
            $this->assertEquals(1, $v->history->where('to', 'voided')->count());
            $this->assertEquals(1, $v->history->where('to', 'retired')->count());
        });
    }

    /** @test */
    public function testItCanExpireVoucherCodes()
    {
        {
            // The post data
            $data = [
                'voucher-start' => 'TST0102',
                'voucher-end' => 'TST0104',
                'transition' => 'expire'
            ];

            // Set some routes
            $formRoute = route('admin.vouchers.void');
            $requestRoute = route('admin.vouchers.retirebatch');
            $successRoute = route('admin.vouchers.index');

            // Set the message to look for
            $msg = trans('service.messages.vouchers_batchretiretransition.success', [
                'transition_to' => 'retired',
                'success_codes' => 'TST0102 TST0103 TST0104',
                'fail_code_details' => '',
            ]);

            // Make the patch
            $this->actingAs($this->user, 'admin')
                ->visit($formRoute)
                ->patch($requestRoute, $data)
                ->followRedirects()
                ->seePageIs($successRoute)
                ->see($msg)
            ;

            // fetch those back.
            $vouchers = Voucher::where('currentstate', 'retired')
                ->with('history')
                ->get()
            ;

            // Check there are 3.
            $this->assertCount(3, $vouchers);

            $vouchers->each(function ($v) {
                $this->assertEquals(1, $v->history->where('to', 'expired')->count());
                $this->assertEquals(1, $v->history->where('to', 'retired')->count());
            });
        }
    }

    /** @test */
    public function testItCanExpireVoucherCodesInABatchWithNonExpireables()
    {
        {
            // The post data
            $data = [
                'voucher-start' => 'TST0102',
                'voucher-end' => 'TST0106',
                'transition' => 'expire'
            ];

            // Set 0104 and 0106 back to printed
            $setToPrinted = Voucher::whereIn('code', ['TST0104', 'TST0106'])->get();
            foreach ($setToPrinted as $v) {
                $v->applyTransition('collect');
                $v->applyTransition('reject-to-printed');
            }

            // Set some routes
            $formRoute = route('admin.vouchers.void');
            $requestRoute = route('admin.vouchers.retirebatch');
            $successRoute = route('admin.vouchers.index');

            // Set the message to look for
            $msg = trans('service.messages.vouchers_batchretiretransition.success', [
                'transition_to' => 'retired',
                'success_codes' => 'TST0102 TST0103 TST0105',
                'fail_code_details' => 'TST0104 TST0106',
            ]);

            // Make the patch
            $this->actingAs($this->user, 'admin')
                ->visit($formRoute)
                ->patch($requestRoute, $data)
                ->followRedirects()
                ->seePageIs($successRoute)
                ->see($msg)
            ;

            // fetch those back.
            $vouchers = Voucher::where('currentstate', 'retired')
                ->with('history')
                ->get()
            ;

            // Check there are 3.
            $this->assertCount(3, $vouchers);

            $vouchers->each(function ($v) {
                $this->assertEquals(1, $v->history->where('to', 'expired')->count());
                $this->assertEquals(1, $v->history->where('to', 'retired')->count());
            });
        }
    }

    /** @test */
    public function testAdminCanVoidASingleVoucher()
    {
        $this->adminUser = factory(AdminUser::class)->create();
        $this->voucherToVoid = factory(Voucher::class, 'dispatched')->create();
        $this->actingAs($this->adminUser, 'admin')
            ->visit(route('admin.vouchers.index'))
            ->seeInElement('td', $this->voucherToVoid->code)
            ->type($this->voucherToVoid->code, 'voucher_code')
            ->press('Search')
            ->seeInElement('td', $this->voucherToVoid->code)
            ->click('edit')
            ->seeInElement('h1', "Voucher Code: " . $this->voucherToVoid->code)
            ->press('transition')
            ;
            $this->seeInDatabase('vouchers', [
                'code' => $this->voucherToVoid->code,
                'currentstate' => 'retired'
            ]);
    }
}
