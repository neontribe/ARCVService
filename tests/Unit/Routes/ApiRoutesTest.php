<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;
use App\Trader;
use App\User;
use Auth;

class ApiRoutesTest extends TestCase
{
    use DatabaseMigrations;

    protected $trader;
    protected $vouchers;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->trader = factory(Trader::class)->create();
        $this->vouchers = factory(Voucher::class, 'requested', 10)->create();
        $this->user = factory(User::class)->create();
        Auth::login($this->user);
        foreach ($this->vouchers as $v) {
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
            $v->applyTransition('allocate');
        }
        $this->vouchers[1]->trader_id = 1;
        $this->vouchers[1]->applyTransition('collect');
    }

    public function testShowTraderVouchersRoute()
    {
        $this->json('GET', route('api.trader.vouchers', 1))
            ->assertJsonStructure([ 0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]])
        ;

    }

    public function testCollectVoucheRoute()
    {
        $payload= [
            'user_id' => 1,
            'trader_id' => 1,
            'vouchers' => [
                'RVP12345563',
            ]
        ];
        $this->json('POST', route('api.voucher.collect'), $payload)
            ->assertJsonStructure([
                'success', 'fail', 'invalid'
            ])
        ;
    }
}
