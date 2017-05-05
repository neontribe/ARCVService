<?php

namespace Tests\Unit\API;

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

    protected $payload = {
        trader_id: 1,
        user_id: 1,
        vouchers: [
            "notavoucher",
            "UYH78787878",
            "SOL00000001",
            "SOL00000002",
            "SOL00000003"
        ]
    };

    protected function setUp()
    {
        parent::setUp();
        // All belong to user1, trader 1.
        $codes = ['SOL00000001', 'SOL00000002', 'SOL00000003', 'SOL00000004'];
        foreach ($codes as $code)
        // Cannot be collected.
        $this->vouchers[] = factory(Voucher::class)->create([
            'code' => $code,
        ];
    }

    public function testVouchersCollect()
    {
        //Todo
    }
    public function testVouchersShow()
    {
        //Todo
    }




}
