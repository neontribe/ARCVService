<?php

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SearchPageTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function itShowsTheLoggedInUser()
    {
        // Create some centres
        factory(App\Centre::class, 4)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => 1,
        ]);

        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
            ->see($user->name)
        ;
    }

    /** @test */
    public function itShowsRegistrationsFromNeighborCentres()
    {
        // Create a single Sponsor
        $sponsor = factory(App\Sponsor::class)->create();

        // Create centres
        $centres = factory(App\Centre::class, 2)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        $centre1 = $centres->first();
        $centre2 = $centres->last();

        // Create a User in Centre 1
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre1->id,
        ]);

        // Make centre1 some registrations
        $registrations1 = factory(App\Registration::class, 4)->create([
            "centre_id" => $centre1->id,
        ]);

        // Make centre2 some registrations
        $registrations2 = factory(App\Registration::class, 4)->create([
            "centre_id" => $centre2->id,
        ]);

        // Visit the page
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'));

        $registrations = $registrations1->concat($registrations2);

        // Check we can see the edit link with the registration ID in it.
        foreach ($registrations as $registration) {
            $edit_url_string = URL::route('service.registration.edit', [ 'id' => $registration->id]);
            $this->see($edit_url_string);
        }
    }


    /** @test */
    public function itShowsRegistrationsFromMyCentre()
    {
        // Create a single Sponsor
        $sponsor = factory(App\Sponsor::class)->create();

        // Create centre
        $centre = factory(App\Centre::class)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        // Create a User in Centre
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Make centre some registrations
        $registrations = factory(App\Registration::class, 4)->create([
            "centre_id" => $centre->id,
        ]);

        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'));

        // Check we can see the edit link with the registration ID in it.
        foreach ($registrations as $registration) {
            $edit_url_string = URL::route('service.registration.edit', [ 'id' => $registration->id]);
            $this->see($edit_url_string);
        }
    }

    /** test */
    public function itDoesNotShowRegistrationsFromUnrelatedCentres()
    {
        // Create a single Sponsor
        $sponsor = factory(App\Sponsor::class)->create();

        // Create centres
        $neighbor_centres = factory(App\Centre::class, 2)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        $alien_centre = factory(App\Centre::class, 2)->create([
            "sponsor_id" => factory(App\Sponsor::class)->create()->id,
        ]);

        $centre1 = $neighbor_centres->first();
        $centre2 = $neighbor_centres->last();

        // Create a User in Centre 1
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre1->id,
        ]);

        // make centre1 some registrations
        factory(App\Registration::class, 4)->create([
            "centre_id" => $centre1->id,
        ]);

        // Make centre2 some registrations
        factory(App\Registration::class, 4)->create([
            "centre_id" => $centre2->id,
        ]);

        // Make alien_centre some registrations
        $registrations3 = factory(App\Registration::class, 4)->create([
            "centre_id" => $alien_centre->id,
        ]);

        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'));

        // Check we can see the edit link with the registration ID in it.
        foreach ($registrations3 as $registration) {
            $edit_url_string = URL::route('service.registration.edit', [ 'id' => $registration->id]);
            $this->dontSee($edit_url_string);
        }
    }

    /** @test */
    public function itShowsThePrimaryCarerName()
    {

        // Create a Centre (and, implicitly a random Sponsor)
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create a random registration.
        $registration = factory(App\Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Get the primary carer
        $pri_carer = $registration->family->carers->first();

        // Spot the Registration Family's primary carer name
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
            ->see($pri_carer->name);
    }

    /** @test */
    public function itShowsTheRVID()
    {
        // Create a Centre
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create a random registration with our centre.
        $registration = factory(App\Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Spot the Registration family's RVID
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
            ->see($registration->family->rvid);
    }

    /** @test */
    public function itShowsTheVoucherEntitlement()
    {
        // Create a Centre
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create a random registration with our centre.
        $registration = factory(App\Registration::class)->create([
            "centre_id" => $centre->id,
        ]);

        // Spot the Registration family's RVID
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
            ->see('<td class="center">' . $registration->family->entitlement . "</td>");
    }

    /** @test */
    public function itPaginatesWhenRequired()
    {
        // Create a single Sponsor
        $sponsor = factory(App\Sponsor::class)->create();

        // Create centre
        $centre = factory(App\Centre::class)->create([
            "sponsor_id" => $sponsor->id,
        ]);

        // Create a User in Centre
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Make centre some registrations
        $registrations = factory(App\Registration::class, 20)->create([
            "centre_id" => $centre->id,
        ]);

        // Visit search page, make sure next page link is present and works
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
            ->see('<a href="' . URL::route('service.base') . '/registration?page=2' . '" rel="next">»</a>')
            ->click('»')
            ->seePageIs(URL::route('service.base') . '/registration?page=2');
    }

    /** @test */
    public function itShowsFamilyPrimaryCarersAlphabetically()
    {
        // Create a Centre (and, implicitly a random Sponsor)
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create a random registration or 5, which should be well under the limit.
        $registrations = factory(App\Registration::class, 5)->create([
            "centre_id" => $centre->id,
        ]);

        //get the primary carers as an array
        $pri_carers = $registrations->map(function ($registration) {
            return $registration->family->carers->first()->name;
        })->toArray();

        sort($pri_carers);

        // Spot the Registration Family's primary carer name
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'));

        $selector = 'td.pri_carer';
        foreach ($pri_carers as $index => $pri_carer) {
            $this->seeInElementAtPos($selector, $pri_carer, $index);
        }
    }

    /** @test */
    public function itHasTheExpectedResultsPerPage()
    {
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create 25 random registrations, which should be well over the pagination limit.
        $registrations = factory(App\Registration::class, 25)->create([
            "centre_id" => $centre->id,
        ]);

        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
        ;


        // Spot  and count the Registrations Family's primary carer names
        $selector = 'td.pri_carer';
        $this->assertCount(10, $this->crawler->filter($selector));
    }

    /** @test */
    public function itCanPaginateWithNumberedPages()
    {
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create 11 random registrations, which should be over the 10 per-page pagination limit.
        $registrations = factory(App\Registration::class, 11)->create([
            "centre_id" => $centre->id,
        ]);

        // Spot the pagination links, expecting 2 pages.
        $selector = 'ul.pagination li';
        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'))
            ->seeInElementAtPos($selector, "«", 0)
            ->seeInElementAtPos($selector, "1", 1)
            ->seeInElementAtPos($selector, "2", 2)
            ->seeInElementAtPos($selector, "»", 3)
        ;
    }

    /** @test */
    public function itShowsLeftFamilyRegistrationsAsDistinct()
    {
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create 10 random registrations, which should be the per-page pagination limit.
        $registrations = factory(App\Registration::class, 10)->create([
            "centre_id" => $centre->id,
        ]);

        // Find and "leave" the first registrations Family
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'));

        $this->assertCount(1, $this->crawler->filter('tr.inactive'));
        $this->assertCount(9, $this->crawler->filter('tr.active'));
    }

    /** @test */
    public function itPreventsAccessToLeftFamilyRegistrations()
    {
        $centre = factory(App\Centre::class)->create();

        // Create a User
        $user =  factory(App\User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $centre->id,
        ]);

        // Create 10 random registrations, which should be the per-page pagination limit.
        $registrations = factory(App\Registration::class, 10)->create([
            "centre_id" => $centre->id,
        ]);

        // Find and "leave" the first registrations Family
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($user)
            ->visit(URL::route('service.registration.index'));

        // Check the number of enabled and disabled buttons.
        $this->assertCount(1, $this->crawler->filter('tr.inactive button:disabled'));
        $this->assertCount(0, $this->crawler->filter('tr.inactive button:enabled'));
        $this->assertCount(0, $this->crawler->filter('tr.active button:disabled'));
        $this->assertCount(9, $this->crawler->filter('tr.active button:enabled'));
    }
}
