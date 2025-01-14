<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\AdminLogin;
use Tests\DuskTestCase;

class AdminAddTradersTest extends DuskTestCase
{
    use RefreshDatabase;

    /** @test */
    public function add_traders_javascript_is_working()
    {
        $adminLogin = new AdminLogin;

        $this->browse(function ($browser) use ($adminLogin) {
            $browser->visit($adminLogin)
                    ->assertSee('Dashboard')
                    ->visit('/traders/create')
                    ->assertSee('Add Trader')
                    ->select('market', $adminLogin->market->id)
                    ->assertSelected('market', $adminLogin->market->id)
                    ->assertSee($adminLogin->market->name)
                    ->type('name', "NewStall")
                    ->type('#new-user-name', "New TraderOne")
                    ->type('#new-user-email', "NewTraderOne@test.com")
                    ->press('Add User')
                    ->assertValue('@trader_name', 'New TraderOne')
                    ->assertValue('@trader_email', 'NewTraderOne@test.com')
                    ->press('.glyphicon-minus')
                    ->assertMissing('@trader_name')
                    ->assertMissing('@trader_email')
                    ;

        });
    }
}
