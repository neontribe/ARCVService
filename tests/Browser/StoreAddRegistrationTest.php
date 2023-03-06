<?php

namespace Tests\Browser;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\StoreLogin;
use Tests\DuskTestCase;

class StoreAddRegistrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function the_JS_is_working_correctly_on_the_create_reg_page()
    {
        $storeLogin = new StoreLogin;
        $age_now = Carbon::now()->subMonths(27)->diff(Carbon::now())->format('%y yr, %m mo');
        $pregnancy = Carbon::now()->addMonths(6);

        $this->browse(function (Browser $browser) use ($storeLogin, $age_now, $pregnancy) {
            $browser->visit($storeLogin)
                    ->assertSee('Add a new Family')
                    ->click('#add-family')
                    ->assertPathIs('/registrations/create')
                    ->assertMissing('.remove_field')
                    ->type('carer_adder_input', "NEW CARER")
                    ->press('#add_collector')
                    ->assertVisible('.remove_field')
                    ->assertVisible('.dob-month')
                    ->assertVisible('.dob-year')
                    ->assertSee('ID Checked')
                    ->assertPresent('.styled-checkbox')
                    ->type('dob-month', Carbon::now()->subMonths(27)->month)
                    ->type('dob-year', Carbon::now()->subMonths(27)->year)
                    ->check('.styled-checkbox')
                    ->press('Add Child or Pregnancy')
                    ->screenshot('test')
                    ->assertSee($age_now)
                    ->assertSee(Carbon::now()->subMonths(27)->format('M') . ' ' . Carbon::now()->subMonths(27)->format('Y'))
                    ->assertChecked('@create_child_dob')
                    ->click('.remove_date_field')
                    ->assertDontSee($age_now)
                    ->assertDontSee(Carbon::now()->subMonths(27)->format('M') . ' ' . Carbon::now()->subMonths(27)->format('Y'))
                    ->assertMissing('@create_child_dob')
                    ->type('dob-month', $pregnancy->month)
                    ->type('dob-year', $pregnancy->year)
                    ->check('.styled-checkbox')
                    ->press('Add Child or Pregnancy')
                    ->assertSeeIn('@pregnancy_col', 'P')
                    ->assertSee($pregnancy->format('M') . ' ' . $pregnancy->format('Y'))
                    ->assertChecked('@create_child_dob')
                    ;
        });
    }
}