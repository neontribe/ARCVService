<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\AdminUser;
use App\Centre;
use App\CentreUser;
use App\Sponsor;

class EditWorkersJSTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function edit_workers_javascript_is_working()
    {
        $sponsor = factory(Sponsor::class)
            ->create();

        $centres = factory(Centre::class, 5)->create([
            'sponsor_id' =>  $sponsor->id
        ]);

        $other_sponsor = factory(Sponsor::class)
            ->create();

        $other_centres = factory(Centre::class, 3)->create([
            'sponsor_id' =>  $other_sponsor->id
        ]);

        // Create a CentreUser
        $adminUser =  factory(AdminUser::class)->create([
            "name"  => "test admin user",
            "email" => "testadminuser@example.com",
            "password" => bcrypt('password_admin'),
        ]);

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create();
        $centreUser->centres()->attach($centres[0]->id, ['homeCentre' => true]);

        $this->browse(function ($browser) use ($adminUser, $centreUser, $centres, $other_centres) {
            $browser->visit('/login')
                    ->assertSee('E-Mail Address')
                    ->assertSee('Password')
                    ->type('email', $adminUser->email)
                    ->type('password', "password_admin")
                    ->press('Login')
                    ->assertSee('Dashboard')
                    ->visit('/workers/' . $centreUser->id . '/edit')
                    ->assertSee('Edit a Children\'s Centre Worker')
                    ->assertSee('Set Neighbours as Alternatives')
                    ->assertSee($centreUser->centre->name)
                    ->assertSelected('worker_centre', $centreUser->centre->id)
                    ->assertPresent('#neighbour-' . $centres[1]->id)
                    ->assertMissing('#neighbour-' . $other_centres[1]->id)
                    ->select('worker_centre', $other_centres[1]->id)
                    ->assertSelected('worker_centre', $other_centres[1]->id)
                    ->assertMissing('#neighbour-' . $centres[2]->id)
                    ->assertPresent('#neighbour-' . $other_centres[2]->id)
                    ->press('Delete worker')
                    ->assertDialogOpened('Are you sure you want to delete this worker account?')
                    ;

        });
    }
}
