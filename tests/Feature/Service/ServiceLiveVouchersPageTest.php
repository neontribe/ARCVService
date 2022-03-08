<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Voucher;
use App\Http\Requests\VoucherSearchRequest;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class ServiceLiveVouchersPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    public function testAdminCanViewLiveVouchers()
    {
        $this->adminUser = factory(AdminUser::class)->create();
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.vouchers.index'))
            ->assertResponseOk()
            ->seeInElement('h1', 'View live vouchers')
            ->seeInElement('button', 'Search')
            ->seeInElement('a', 'Reset')
            ;
    }

    public function testAdminCanSearchASingleLiveVoucher()
    {
        $this->adminUser = factory(AdminUser::class)->create();
        $this->voucherToSearch = factory(Voucher::class, 'dispatched')->create();
        $this->otherVoucher = factory(Voucher::class, 'dispatched')->create();
        $this->actingAs($this->adminUser, 'admin')
            ->visit(route('admin.vouchers.index'))
            ->seeInElement('td', $this->voucherToSearch->code)
            ->seeInElement('td', $this->otherVoucher->code)
            ->type($this->voucherToSearch->code, 'voucher_code')
            ->press('Search')
            ->seeInElement('td', $this->voucherToSearch->code)
            ->dontSeeInElement('td', $this->otherVoucher->code)
            ;
    }

    public function testAdminCannotSearchWithBadVoucherCode()
    {
        $this->adminUser = factory(AdminUser::class)->create();
        $badSearch = "<script type='javascript'>alert();</script>";
        $this->voucher = factory(Voucher::class, 'dispatched')->create();
        $this->actingAs($this->adminUser, 'admin')
            ->visit(route('admin.vouchers.index'))
            ->seeInElement('td', $this->voucher->code)
            ->type($badSearch, 'voucher_code')
            ->press('Search')
            ->seeInElement('h1', 'View live vouchers')
            ;
    }

    public function testAdminCanViewHistoryOfSingleVoucher()
    {
        $this->adminUser = factory(AdminUser::class)->create();
        $this->voucherToSearch = factory(Voucher::class, 'dispatched')->create();
        $this->actingAs($this->adminUser, 'admin')
            ->visit(route('admin.vouchers.index'))
            ->seeInElement('td', $this->voucherToSearch->code)
            ->type($this->voucherToSearch->code, 'voucher_code')
            ->press('Search')
            ->seeInElement('td', $this->voucherToSearch->code)
            ->click('edit')
            ->seeInElement('h1', "Voucher Code: " . $this->voucherToSearch->code)
            ;
    }

    public function testAdminCanResetSearch()
    {
        $this->adminUser = factory(AdminUser::class)->create();
        $this->voucherToSearch = factory(Voucher::class, 'dispatched')->create();
        $this->otherVoucher = factory(Voucher::class, 'dispatched')->create();
        $this->actingAs($this->adminUser, 'admin')
            ->visit(route('admin.vouchers.index'))
            ->seeInElement('td', $this->voucherToSearch->code)
            ->seeInElement('td', $this->otherVoucher->code)
            ->type($this->voucherToSearch->code, 'voucher_code')
            ->press('Search')
            ->seeInElement('td', $this->voucherToSearch->code)
            ->dontSeeInElement('td', $this->otherVoucher->code)
            ->click('reset')
            ->seePageIs(route('admin.vouchers.index'))
            ->seeInElement('td', $this->voucherToSearch->code)
            ->seeInElement('td', $this->otherVoucher->code)
            ;
    }
}
