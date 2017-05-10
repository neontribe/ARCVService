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
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testVouchersResourseRoutes()
    {
        $this->get(route('vouchers.index'))
            ->assertStatus(200)
        ;

        $this->json('GET', route('vouchers.index'))
            ->assertJsonStructure([ 0 => [
                'id', 'trader_id', 'code', 'currentstate', 'sponsor_id'
            ]])
        ;
    }
}
