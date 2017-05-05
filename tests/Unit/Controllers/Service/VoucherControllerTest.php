<?php

namespace Tests\Unit\Service;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Voucher;
use App\User;
use Auth;

class VoucherControllerTest extends TestCase
{

    use DatabaseMigrations, DatabaseTransactions;

    protected $vouchers;
    protected function setUp()
    {
        parent::setUp();
        $this->vouchers = factory(Voucher::class, 20)->create();
    }

    public function testVouchersIndex()
    {

    }
    public function testVouchersStore()
    {
        //Todo
    }
    public function testVouchersShow()
    {
        //Todo
    }




}
