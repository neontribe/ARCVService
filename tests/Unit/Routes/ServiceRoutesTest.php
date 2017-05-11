<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;

class ServiceRoutesTest extends TestCase
{
   use DatabaseMigrations;

    protected $vouchers;

    protected function setUp()
    {
        parent::setUp();
        $this->vouchers = factory(Voucher::class, 10)->create();
     }

    public function testVouchersIndexRoute()
    {
        $this->get(route('vouchers.index'))
            ->assertStatus(200)
            ->assertJsonStructure([ 0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]])
        ;
    }

    public function testVouchersShowRoute()
    {
        $this->get(route('vouchers.show', $this->vouchers[0]))
            ->assertStatus(200)
            ->assertJsonStructure(['id', 'trader_id', 'code', 'currentstate', 'sponsor_id'])
        ;
    }

}
