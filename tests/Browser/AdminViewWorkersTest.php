<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Sponsor;

class AdminViewWorkersTest extends DuskTestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_view_workers_datatable_is_functioning()
    {
        $sponsor = factory(Sponsor::class)
            ->create();

        $centre = factory(Centre::class)->create([
            'sponsor_id' =>  $sponsor->id
        ]);

        $centreUsers =  factory(CentreUser::class, 15)->create();
        foreach ($centreUsers as $centreUser) {
            $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
        }

        // Create a CentreUser
        $adminUser =  factory(AdminUser::class)->create([
            "name"  => "test admin user",
            "email" => "testadminuser@example.com",
            "password" => bcrypt('password_admin'),
        ]);

        $this->browse(function ($browser) use ($adminUser, $centreUsers) {
            $browser->visit('/login')
                    ->assertSee('E-Mail Address')
                    ->assertSee('Password')
                    ->type('email', $adminUser->email)
                    ->type('password', "password_admin")
                    ->press('Login')
                    ->assertSee('Dashboard')
                    ->visit('/workers')
                    ->assertSee('Workers')
                    ->assertSee('Search:')
                    ->assertSee('Next')
                    ->resize(1920, 3000)
                    ->assertSee('Showing 1 to 10 of 15 entries')
                    ;
            $this->assertCount(10, $browser->elements('td.sorting_1'));

            $browser->resize(1920, 3000)
                    ->click('#workersTable_next')
                    ;
            $this->assertCount(5, $browser->elements('td.sorting_1'));

            $browser->resize(1920, 3000)
                    ->click('#workersTable_previous')
                    ;
            $this->assertCount(10, $browser->elements('td.sorting_1'));
            $browser->select('workersTable_length', '100');
            $this->assertCount(15, $browser->elements('td.sorting_1'));
            $browser->assertSee($centreUsers[0]->name)
                    ->type('input[type=search]', $centreUsers[0]->name);
            $this->assertCount(1, $browser->elements('td.sorting_1'));
            $browser->assertDontSee($centreUsers[1]->name);
            $browser->resize(1920, 3000)
                    ->assertSee('Showing 1 to 1 of 1 entries (filtered from 15 total entries)');

        });
    }
}
