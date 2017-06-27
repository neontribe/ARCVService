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
                ->assertSeeLink('Vouchers')
                ->assertSeeLink('Users')
                ->assertSeeLink('Traders')
                ->assertSeeLink('Markets')
                ->assertSeeLink('Reset data')
                // Just testing one of the routes for now.
                ->click('@button', 'Vouchers')
                ->assertPathIs('/service/vouchers')
            ;
        });
    }

    /**
     * Test that production does not have data routes.
     *
     * @return void

    public function testDashboardContentProduction()
    {
        // Todo Can't make this work.
        Config::set('app.url', 'https://voucher-admin.alexandrarose.org.uk');
        $this->browse(function (Browser $browser) {
            $browser
                ->visit(new Dashboard)
                ->assertDontSeeLink('Vouchers')
                ->assertDontSeeLink('Users')
                ->assertDontSeeLink('Traders')
                ->assertDontSeeLink('Markets')
                ->assertDontSeeLink('Reset data')
            ;
        });
    }
    */
}
