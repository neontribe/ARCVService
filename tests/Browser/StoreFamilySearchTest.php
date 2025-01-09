<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\StoreLogin;
use Tests\DuskTestCase;

class StoreFamilySearchTest extends DuskTestCase
{
    use RefreshDatabase;

    /** @test */
    public function family_search_datatable_is_working()
    {
        $storeLogin = new StoreLogin;
        $pri_carer = $storeLogin->registrations[0]->family->carers->first()->name;
        $another_pri_carer = $storeLogin->registrations[1]->family->carers->first()->name;

        $this->browse(function (Browser $browser) use ($storeLogin, $pri_carer, $another_pri_carer) {
            $browser->visit($storeLogin)
                    ->assertSee('Search for a Family')
                    ->click('#search')
                    ->assertPathIs('/registrations')
                    ->assertSee('Search:')
                    ->assertSee('Next')
                    ->resize(1920, 3000)
                    ->assertSee('Showing 1 to 10 of 15 entries')
                    ;
            $this->assertCount(10, $browser->elements('td.sorting_1'));

            $browser->resize(1920, 3000)
                    ->click('#registrationTable_next')
                    ;
            $this->assertCount(5, $browser->elements('td.sorting_1'));

            $browser->resize(1920, 3000)
                    ->click('#registrationTable_previous')
                    ;
            $this->assertCount(10, $browser->elements('td.sorting_1'));
            $browser->select('registrationTable_length', '100');
            $this->assertCount(15, $browser->elements('td.sorting_1'));
            $browser->assertSee($pri_carer)
                    ->type('input[type=search]', $pri_carer);
            $this->assertCount(1, $browser->elements('td.sorting_1'));
            $browser->assertDontSee($another_pri_carer);
            $browser->resize(1920, 3000)
                    ->assertSee('Showing 1 to 1 of 1 entries (filtered from 15 total entries)')
                    ;
        });
    }
}
