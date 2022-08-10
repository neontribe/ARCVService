<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\AdminUser;
use App\Centre;
use App\Sponsor;

class AdminViewCentresTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function the_view_centres_datatable_is_functioning()
    {
        $sponsor = factory(Sponsor::class)
            ->create();

        $centres = factory(Centre::class, 15)->create([
            'sponsor_id' =>  $sponsor->id
        ]);

        // Create a CentreUser
        $adminUser =  factory(AdminUser::class)->create([
            "name"  => "test admin user",
            "email" => "testadminuser@example.com",
            "password" => bcrypt('password_admin'),
        ]);

        $this->browse(function ($browser) use ($adminUser, $centres) {
            $browser->visit('/login')
                    ->assertSee('E-Mail Address')
                    ->assertSee('Password')
                    ->type('email', $adminUser->email)
                    ->type('password', "password_admin")
                    ->press('Login')
                    ->assertSee('Dashboard')
                    ->visit('/centres')
                    ->assertSee('Children\'s Centres')
                    ->assertSee('Search:')
                    ->assertSee('Next')
                    ->resize(1920, 3000)
                    ->assertSee('Showing 1 to 10 of 15 entries')
                    ;
            $this->assertCount(10, $browser->elements('td.sorting_1'));

            $browser->resize(1920, 3000)
                    ->click('#centresTable_next')
                    ;
            $this->assertCount(5, $browser->elements('td.sorting_1'));

            $browser->resize(1920, 3000)
                    ->click('#centresTable_previous')
                    ;
            $this->assertCount(10, $browser->elements('td.sorting_1'));
            $browser->select('centresTable_length', '100');
            $this->assertCount(15, $browser->elements('td.sorting_1'));
            $browser->assertSee($centres[0]->name)
                    ->type('input[type=search]', $centres[0]->name);
            $this->assertCount(1, $browser->elements('td.sorting_1'));
            $browser->assertDontSee($centres[1]->name);
            $browser->resize(1920, 3000)
                    ->assertSee('Showing 1 to 1 of 1 entries (filtered from 15 total entries)');

        });
    }
}
