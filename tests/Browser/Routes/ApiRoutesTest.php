<?php

namespace Tests\Browser\Routes;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Voucher;
use App\Trader;

class ApiRoutesTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testShowTraderVouchers()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('api/traders/1/vouchers')
                    ->assertSee('trader_id');
        });
    }
}
