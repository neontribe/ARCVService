<?php

namespace Tests\Browser;

use Tests\Browser\Pages\Dashboard;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Config;

class DashboardTest extends DuskTestCase
{
    /**
     * Test that all enirons except production have data routes.
     *
     * @return void
     */
    public function testDashboardContentNotProduction()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(new Dashboard)
                // Right now this is redirected to login page.
                ->assertPathIs('/login')
                //->assertSeeLink('Vouchers')
                //->assertSeeLink('Users')
                //->assertSeeLink('Traders')
                //->assertSeeLink('Markets')
                //->assertSeeLink('Reset data')
                // Just testing one of the routes for now.
                //->click('@button', 'Vouchers')
                //->assertPathIs('/data/vouchers')
            ;
        });
    }
}
