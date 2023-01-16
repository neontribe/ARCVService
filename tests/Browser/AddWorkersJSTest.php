<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\AdminLogin;
use Tests\DuskTestCase;

class AddWorkersJSTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function add_workers_javascript_is_working()
    {
        $adminLogin = new AdminLogin;

        $this->browse(function ($browser) use ($adminLogin) {
            $browser->visit($adminLogin)
                    ->assertSee('Dashboard')
                    ->visit('/workers/create')
                    ->assertSee('Add a Children\'s Centre Worker')
                    ->select('worker_centre', 1)
                    ->assertSee($adminLogin->centres[0]->name)
                    ->assertSelected('worker_centre', 1)
                    ->assertSee('Set Neighbours as Alternatives')
                    ->assertPresent('#neighbour-' . $adminLogin->centres[1]->id)
                    ->assertMissing('#neighbour-' . $adminLogin->other_centres[1]->id)
                    ;
        });
    }
}