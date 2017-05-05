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

    protected $vouchers, $user;
    protected $payload = [
        'trader_id' => 1,
        'user_id' => 1,
        'vouchers' => [
            'notavoucher',
            'UYH78787878',
            'SOL00000001',
            'SOL00000002',
            'SOL00000003'
        ]
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->user = factory(User::class)->create();

        // All belong to user1, trader 1.
        $codes = ['SOL00000001', 'SOL00000002', 'SOL00000003', 'SOL00000004'];
        foreach ($codes as $code) {
            // Cannot be collected.
            $this->vouchers[] = factory(Voucher::class)->create([
                'code' => $code,
            ]);
        }
    }

    public function testVouchersCollect()
    {
        $this->actingAs($this->user)
            ->json('POST', route('api.voucher.collect'), $this->payload);
            // Not getting the type of response object I was expecting.
            //->dump();
            //->seeStatusCode(200);
    }
    public function testVouchersShow()
    {
        //Todo
    }




}
