<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\AdminLogin;
use Tests\DuskTestCase;

class AdminEditWorkersJSTest extends DuskTestCase
{
    use RefreshDatabase;

    /** @test */
    public function edit_workers_javascript_is_working()
    {
        $adminLogin = new AdminLogin;

        $this->browse(function ($browser) use ($adminLogin) {
            $browser->visit($adminLogin)
                    ->visit('/workers/' . $adminLogin->centreUser->id . '/edit')
                    ->assertSee('Edit a Children\'s Centre Worker')
                    ->assertSee('Set Neighbours as Alternatives')
                    ->assertSee($adminLogin->centreUser->centre->name)
                    ->assertSelected('worker_centre', $adminLogin->centreUser->centre->id)
                    ->assertPresent('#neighbour-' . $adminLogin->centres[1]->id)
                    ->assertMissing('#neighbour-' . $adminLogin->other_centres[1]->id)
                    ->select('worker_centre', $adminLogin->other_centres[1]->id)
                    ->assertSelected('worker_centre', $adminLogin->other_centres[1]->id)
                    ->assertMissing('#neighbour-' . $adminLogin->centres[2]->id)
                    ->assertPresent('#neighbour-' . $adminLogin->other_centres[2]->id)
                    ->press('Delete worker')
                    ->assertDialogOpened('Are you sure you want to delete this worker account?')
                    ;
        });
    }
}
