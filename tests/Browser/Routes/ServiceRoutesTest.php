<?php

namespace Tests\Browser\Routes;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ServiceRoutesTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testVouchersResourseRoutes()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('service/vouchers')
                    ->assertSee('trader_id');
        });
    }
}
