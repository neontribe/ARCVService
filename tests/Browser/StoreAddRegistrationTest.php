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
        $knownDate = Carbon::create(2022, 8, 10, 12);
        Carbon::setTestNow($knownDate);
        $age_now = Carbon::parse('2018-12-01')->diff(\Carbon\Carbon::now())->format('%y yr, %m mo');

        $this->browse(function (Browser $browser) use ($storeLogin, $age_now) {
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
                    ->type('dob-month', 12)
                    ->type('dob-year', 2018)
                    ->check('.styled-checkbox')
                    ->press('Add Child or Pregnancy')
                    ->screenshot('test')
                    ->assertSee($age_now)
                    ->assertSee('Dec 2018')
                    ->assertChecked('@create_child_dob')
                    ->click('.remove_date_field')
                    ->assertDontSee($age_now)
                    ->assertDontSee('Dec 2018')
                    ->assertMissing('@create_child_dob')
                    ->type('dob-month', 12)
                    ->type('dob-year', 2022)
                    ->check('.styled-checkbox')
                    ->press('Add Child or Pregnancy')
                    ->assertSeeIn('@pregnancy_col', 'P')
                    ->assertSee('Dec 2022')
                    ->assertChecked('@create_child_dob')
                    ;
        });
        // Set Carbon date & time back
        Carbon::setTestNow();
    }
}
