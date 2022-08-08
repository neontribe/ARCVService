<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\AdminUser;
use App\Centre;
use App\Sponsor;

class AddWorkersJSTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function add_workers_javascript_is_working()
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

        $this->browse(function ($browser) use ($adminUser, $centres, $other_centres) {
            $browser->visit('/login')
                    ->assertSee('E-Mail Address')
                    ->assertSee('Password')
                    ->type('email', $adminUser->email)
                    ->type('password', "password_admin")
                    ->press('Login')
                    ->assertSee('Dashboard')
                    ->visit('/workers/create')
                    ->assertSee('Add a Children\'s Centre Worker')
                    ->select('worker_centre', 1)
                    ->assertSee($centres[0]->name)
                    ->assertSelected('worker_centre', 1)
                    ->assertSee('Set Neighbours as Alternatives')
                    ->assertPresent('#neighbour-' . $centres[1]->id)
                    ->assertMissing('#neighbour-' . $other_centres[1]->id)
                    ;

        });
    }
}
