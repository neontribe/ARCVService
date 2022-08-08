<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\AdminUser;
use App\Market;
use App\Sponsor;

class AdminAddTradersTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function add_traders_javascript_is_working()
    {
        $sponsor = factory(Sponsor::class)
            ->create();

        $market = factory(Market::class)->create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Test Market',
        ]);

        // Create a CentreUser
        $adminUser =  factory(AdminUser::class)->create([
            "name"  => "test admin user",
            "email" => "testadminuser@example.com",
            "password" => bcrypt('password_admin'),
        ]);

        $this->browse(function ($browser) use ($adminUser, $market) {
            $browser->visit('/login')
                    ->assertSee('E-Mail Address')
                    ->assertSee('Password')
                    ->type('email', $adminUser->email)
                    ->type('password', "password_admin")
                    ->press('Login')
                    ->assertSee('Dashboard')
                    ->visit('/traders/create')
                    ->assertSee('Add Trader')
                    ->select('market', $market->id)
                    ->assertSelected('market', $market->id)
                    ->assertSee($market->name)
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
