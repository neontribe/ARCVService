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
    }

    public function testVouchersIndex()
    {
        // This will change but is currently a JSON endpoint.
        // The status and structure are being tested in Unit/Routes/ServiceRoutesTest
    }

    public function testVouchersShow()
    {
        // This will change but currently JSON for easier dev.
        // The status and structure tested in Unit/Routes/ServiceRoutesTest
    }




}
