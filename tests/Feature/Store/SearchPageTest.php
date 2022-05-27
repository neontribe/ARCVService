<?php
namespace Tests\Feature\Store;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Carbon\Carbon;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class SearchPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function itShowsTheLoggedInUser()
    {
        // Create some centres
        factory(Centre::class, 4)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach(1, ['homeCentre' => true]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see($centreUser->name)
        ;
    }

    /** @test */
    public function itShowsRegistrationsFromNeighbourCentres()
    {
        // Create a single Sponsor
        $sponsor = factory(Sponsor::class)->create();

        // Create centres
        $centres = factory(Centre::class, 2)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        $centre1 = $centres->first();
        $centre2 = $centres->last();

        // Create a CentreUser in Centre 1
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre1->id, ['homeCentre' => true]);

        // Make centre1 some registrations
        $registrations1 = factory(Registration::class, 4)->create([
            "centre_id" => $centre1->id,
        ]);

        // Make centre2 some registrations
        $registrations2 = factory(Registration::class, 4)->create([
            "centre_id" => $centre2->id,
        ]);

        // Visit the page
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        $registrations = $registrations1->concat($registrations2);

        // Check we can see the edit link with the registration ID in it.
        foreach ($registrations as $registration) {
            $edit_url_string = URL::route('store.registration.edit', [ 'registration' => $registration->id]);
            $this->see($edit_url_string);
        }
    }


    /** @test */
    public function itShowsRegistrationsFromMyCentre()
    {
        // Create a single Sponsor
        $sponsor = factory(Sponsor::class)->create();

        // Create centre
        $centre = factory(Centre::class)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        // Create a CentreUser in Centre
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Make centre some registrations
        $registrations = factory(Registration::class, 4)->create([
            "centre_id" => $centre->id,
        ]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        // Check we can see the edit link with the registration ID in it.
        foreach ($registrations as $registration) {
            $edit_url_string = URL::route('store.registration.edit', [ 'registration' => $registration->id]);
            $this->see($edit_url_string);
        }
    }

    /** test */
    public function itDoesNotShowRegistrationsFromUnrelatedCentres()
    {
        // Create a single Sponsor
        $sponsor = factory(Sponsor::class)->create();

        // Create centres
        $neighbour_centres = factory(Centre::class, 2)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        $alien_centre = factory(Centre::class, 2)->create([
            "sponsor_id" => factory(Sponsor::class)->create()->id,
        ]);

        $centre1 = $neighbour_centres->first();
        $centre2 = $neighbour_centres->last();

        // Create a CentreUser in Centre 1
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre1->id, ['homeCentre' => true]);

        // make centre1 some registrations
        factory(Registration::class, 4)->create([
            "centre_id" => $centre1->id,
        ]);

        // Make centre2 some registrations
        factory(Registration::class, 4)->create([
            "centre_id" => $centre2->id,
        ]);

        // Make alien_centre some registrations
        $registrations3 = factory(Registration::class, 4)->create([
            "centre_id" => $alien_centre->id,
        ]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        // Check we can see the edit link with the registration ID in it.
        foreach ($registrations3 as $registration) {
            $edit_url_string = URL::route('store.registration.edit', [ 'id' => $registration->id]);
            $this->dontSee($edit_url_string);
        }
    }

    /** @test */
    public function itShowsThePrimaryCarerName()
    {

        // Create a Centre (and, implicitly a random Sponsor)
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a random registration.
        $registration = factory(Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Get the primary carer
        $pri_carer = $registration->family->carers->first();

        // Spot the Registration Family's primary carer name
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see($pri_carer->name);
    }

    /** @test */
    public function itShowsTheRVID()
    {
        // Create a Centre
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Spot the Registration family's RVID
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see($registration->family->rvid);
    }

    /** @test */
    public function itShowsTheVoucherEntitlement()
    {
        // Create a Centre
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a random registration with our centre.
        $registration = factory(Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Spot the Registration family's RVID
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see('<td class="center">' . $registration->getValuation()->getEntitlement() . "</td>");
    }

    /** @test */
    public function itShowsFamilyPrimaryCarersAlphabetically()
    {
        // Create a Centre (and, implicitly a random Sponsor)
        $centre = factory(Centre::class)->create();

        // Create a centreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a random registration or 5, which should be well under the limit.
        $registrations = factory(Registration::class, 5)->create([
            "centre_id" => $centre->id,
        ]);

        //get the primary carers as an array
        $pri_carers = $registrations->map(function ($registration) {
            return $registration->family->carers->first()->name;
        })->toArray();

        sort($pri_carers);

        // Spot the Registration Family's primary carer name
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        $selector = 'td.pri_carer';
        foreach ($pri_carers as $index => $pri_carer) {
            $this->seeInElementAtPos($selector, $pri_carer, $index);
        }
    }

    /** @test */
    public function itHasTheExpectedResultsPerPage()
    {
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create 25 random registrations, which should be well over the pagination limit.
        factory(Registration::class, 25)->create([
            "centre_id" => $centre->id,
        ]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
        ;


        // Spot  and count the Registrations Family's primary carer names
        $selector = 'td.pri_carer';
        $this->assertCount(25, $this->crawler->filter($selector));
    }

    /** @test */
    public function itShowsCentreLabelsForUsersByDefault()
    {
        // Create some centres
        $centre1 = factory(Centre::class)->create([
            "name" => "Tatooine"
        ]);
        $centre2 = factory(Centre::class)->create([
            "name" => "Dagobah"
        ]);
        $centre3 = factory(Centre::class)->create([
            "name" => "Coruscant"
        ]);

        // Create a Centre User
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre1->id, ['homeCentre' => true]);

        // Create some registrations in different centres
        factory(Registration::class, 4)->create([
            "centre_id" => $centre1->id,
        ]);
        factory(Registration::class, 3)->create([
            "centre_id" => $centre2->id,
        ]);
        factory(Registration::class, 2)->create([
            "centre_id" => $centre3->id,
        ]);

        // Check that we can see the centre labels in the page
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see('Tatooine')
            ->see('Dagobah')
            ->see('Coruscant');

        // Check that each user we have added has a secondary info field
        $this->assertCount(9, $this->crawler->filter('div.secondary_info'));
    }

    /** @test */
    public function itDoesNotShowLeftFamiliesByDefault()
    {
        $centre = factory(Centre::class)->create();

        // Create a Centre User
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create 10 random registrations, which should be the per-page pagination limit.
        $registrations = factory(Registration::class, 10)->create([
            "centre_id" => $centre->id,
        ]);

        // Find and "leave" the first registrations Family
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->dontSee($leavingFamily->carers->first());
    }

    /** @test */
    public function itShowsLeftFamilyRegistrationsAsDistinct()
    {
        $centre = factory(Centre::class)->create();

        // Create a Centre User
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create 10 random registrations, which should be the per-page pagination limit.
        $registrations = factory(Registration::class, 10)->create([
            "centre_id" => $centre->id,
        ]);

        // Find and "leave" the first registrations Family
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->check('#families_left');

        $this->assertCount(1, $this->crawler->filter('tr.inactive'));
        $this->assertCount(9, $this->crawler->filter('tr.active'));
    }

    /** @test */
    public function itPreventsAccessToLeftFamilyRegistrations()
    {
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create 10 random registrations, which should be the per-page pagination limit.
        $registrations = factory(Registration::class, 10)->create([
            "centre_id" => $centre->id,
        ]);

        // Find and "leave" the first registrations Family
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->check('#families_left');

        // Check the number of enabled and disabled buttons.
        $this->assertCount(2, $this->crawler->filter('tr.inactive td.right.no-wrap div.disabled'));
        $this->assertCount(0, $this->crawler->filter('tr.inactive td.right.no-wrap div:not(.disabled)'));
        $this->assertCount(0, $this->crawler->filter('tr.active td.right.no-wrap div.disabled'));
        $this->assertCount(18, $this->crawler->filter('tr.active td.right.no-wrap div:not(.disabled)'));
    }

    /** @test */
    public function aVouchersButtonIsPresent()
    {
        // Create a Centre
        $centre = factory(Centre::class)->create();

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        // Create a random registration with our centre.
        factory(Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Find a vouchers button
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see('Vouchers');
    }
}
