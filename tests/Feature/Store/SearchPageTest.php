<?php

namespace Tests\Feature\Store;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\StoreTestCase;
use URL;

class SearchPageTest extends StoreTestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function userInCentre(Centre $centre): CentreUser
    {
        $user = factory(CentreUser::class)->create();
        $user->centres()->attach($centre->id, ['homeCentre' => true]);
        return $user;
    }

    /** Force a known name onto the primary (lowest-id) carer of a registration. */
    private function setCarerName(Registration $reg, string $name): void
    {
        $reg->family->carers()->orderBy('id')->first()->update(['name' => $name]);
    }

    // ── Rendering & content ───────────────────────────────────────────────────

    public function testItShowsTheLoggedInUser(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see($centreUser->name);
    }

    public function testItShowsThePrimaryCarerName(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $pri_carer = $registration->family->carers->first();

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see($pri_carer->name);
    }

    public function testItShowsTheRVID(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);
        $registration = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see($registration->family->rvid);
    }

    public function testItShowsCentreLabelsForUsersByDefault(): void
    {
        $centre1 = factory(Centre::class)->create(['name' => 'Tatooine']);
        $centre2 = factory(Centre::class)->create(['name' => 'Dagobah']);
        $centre3 = factory(Centre::class)->create(['name' => 'Coruscant']);

        $centreUser = $this->userInCentre($centre1);

        factory(Registration::class, 4)->create(['centre_id' => $centre1->id]);
        factory(Registration::class, 3)->create(['centre_id' => $centre2->id]);
        factory(Registration::class, 2)->create(['centre_id' => $centre3->id]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see('Tatooine')
            ->see('Dagobah')
            ->see('Coruscant');

        $this->assertCount(9, $this->crawler->filter('div.secondary_info'));
    }

    public function testAVouchersButtonIsPresent(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);
        factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->see('Vouchers');
    }

    // ── Centre scoping ────────────────────────────────────────────────────────

    public function testItShowsRegistrationsFromNeighbourCentres(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $centres = factory(Centre::class, 2)->create(['sponsor_id' => $sponsor->id]);
        $centre1 = $centres->first();
        $centre2 = $centres->last();

        $centreUser = $this->userInCentre($centre1);
        $registrations = factory(Registration::class, 4)->create(['centre_id' => $centre1->id])
            ->concat(factory(Registration::class, 4)->create(['centre_id' => $centre2->id]));

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        foreach ($registrations as $registration) {
            $this->see(URL::route('store.registration.edit', ['registration' => $registration->id]));
        }
    }

    public function testItShowsRegistrationsFromMyCentre(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $centre = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $centreUser = $this->userInCentre($centre);

        $registrations = factory(Registration::class, 4)->create(['centre_id' => $centre->id]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        foreach ($registrations as $registration) {
            $this->see(URL::route('store.registration.edit', ['registration' => $registration->id]));
        }
    }

    public function testItDoesNotShowRegistrationsFromUnrelatedCentres(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $neighbourCentres = factory(Centre::class, 2)->create(['sponsor_id' => $sponsor->id]);
        $alienCentre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ]);

        $centreUser = $this->userInCentre($neighbourCentres->first());

        factory(Registration::class, 4)->create(['centre_id' => $neighbourCentres->first()->id]);
        factory(Registration::class, 4)->create(['centre_id' => $neighbourCentres->last()->id]);
        $alienRegistrations = factory(Registration::class, 4)->create(['centre_id' => $alienCentre->id]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        foreach ($alienRegistrations as $registration) {
            $this->dontSee(URL::route('store.registration.edit', ['registration' => $registration->id]));
        }
    }

    public function testItScopesToSessionCentreWhenFilterByCentreIsSet(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $centre1 = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $centre2 = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);

        // User must belong to both centres for the filter_by_centre guard to trigger
        $user = factory(CentreUser::class)->create();
        $user->centres()->attach($centre1->id, ['homeCentre' => true]);
        $user->centres()->attach($centre2->id, ['homeCentre' => false]);

        $reg1 = factory(Registration::class)->create(['centre_id' => $centre1->id]);
        $reg2 = factory(Registration::class)->create(['centre_id' => $centre2->id]);

        $this->actingAs($user, 'store')
            ->withSession(['CentreUserCurrentCentreId' => $centre1->id])
            ->visit(URL::route('store.registration.index', ['filter_by_centre' => '1']));

        $this->see(URL::route('store.registration.edit', $reg1));
        $this->dontSee(URL::route('store.registration.edit', $reg2));
    }

    public function testItIgnoresCentreFilterForSingleCentreUser(): void
    {
        // The filter_by_centre guard requires centres->count() > 1; single-centre
        // users are unaffected and continue to see their full neighbour set.
        $sponsor = factory(Sponsor::class)->create();
        $centre1 = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $centre2 = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);

        $user = $this->userInCentre($centre1);

        $reg1 = factory(Registration::class)->create(['centre_id' => $centre1->id]);
        $reg2 = factory(Registration::class)->create(['centre_id' => $centre2->id]);

        $this->actingAs($user, 'store')
            ->withSession(['CentreUserCurrentCentreId' => $centre1->id])
            ->visit(URL::route('store.registration.index', ['filter_by_centre' => '1']));

        $this->see(URL::route('store.registration.edit', $reg1));
        $this->see(URL::route('store.registration.edit', $reg2));
    }

    // ── Strategy selection ────────────────────────────────────────────────────

    public function testItUsesExactPathWhenFamilyNameIsAbsent(): void
    {
        // fuzzy=1 with no name → $familyName->isEmpty() → $useFuzzy is false
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);
        $reg = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', ['fuzzy' => '1']));

        // Registration visible confirms the exact path ran without Searchy
        $this->see(URL::route('store.registration.edit', $reg));
    }

    public function testItUsesExactPathWhenDriverIsNotMysql(): void
    {
        // Default test driver is SQLite; even with fuzzy=1 + name the exact path runs
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);
        $reg = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $name = $reg->family->carers()->orderBy('id')->first()->name;

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => $name,
                'fuzzy' => '1',
            ]));

        $this->see(URL::route('store.registration.edit', $reg));
    }

    // ── Filtering ─────────────────────────────────────────────────────────────

    public function testItFiltersResultsByCarerNameSubstring(): void
    {
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);
        $matching = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $other = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->setCarerName($matching, 'Zelda Unique');
        $this->setCarerName($other, 'Other Person');

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', ['family_name' => 'Zelda']));

        $this->see(URL::route('store.registration.edit', $matching));
        $this->dontSee(URL::route('store.registration.edit', $other));
    }

    // ── Ordering ──────────────────────────────────────────────────────────────

    public function testItShowsFamilyPrimaryCarersAlphabetically(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);

        $regs = factory(Registration::class, 3)->create(['centre_id' => $centre->id]);
        $this->setCarerName($regs[0], 'Charlie');
        $this->setCarerName($regs[1], 'Alice');
        $this->setCarerName($regs[2], 'Bob');

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        $this->seeInElementAtPos('td.pri_carer', 'Alice', 0);
        $this->seeInElementAtPos('td.pri_carer', 'Bob', 1);
        $this->seeInElementAtPos('td.pri_carer', 'Charlie', 2);
    }

    public function testItOrdersCarerNamesDescending(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);

        $regs = factory(Registration::class, 3)->create(['centre_id' => $centre->id]);
        $this->setCarerName($regs[0], 'Charlie');
        $this->setCarerName($regs[1], 'Alice');
        $this->setCarerName($regs[2], 'Bob');

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index', ['direction' => 'desc']));

        $this->seeInElementAtPos('td.pri_carer', 'Charlie', 0);
        $this->seeInElementAtPos('td.pri_carer', 'Bob', 1);
        $this->seeInElementAtPos('td.pri_carer', 'Alice', 2);
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    public function testItHasTheExpectedResultsPerPage(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);
        factory(Registration::class, 15)->create(['centre_id' => $centre->id]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'));

        $this->assertCount(10, $this->crawler->filter('td.pri_carer'));
    }

    public function testItRedirectsToLastPageWhenRequestedPageExceedsLastPage(): void
    {
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);
        factory(Registration::class, 5)->create(['centre_id' => $centre->id]);

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', ['page' => '99']));

        $this->seePageIs(URL::route('store.registration.index', ['page' => '1']));
    }

    // ── Left families ─────────────────────────────────────────────────────────

    public function testItDoesNotShowLeftFamiliesByDefault(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);

        $registrations = factory(Registration::class, 10)->create(['centre_id' => $centre->id]);
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->dontSee($leavingFamily->carers->first()->name);
    }

    public function testItIncludesLeftFamiliesWhenFamiliesLeftIsRequested(): void
    {
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);
        $regs = factory(Registration::class, 2)->create(['centre_id' => $centre->id]);

        $regs->first()->family->update(['leaving_on' => Carbon::now()]);

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index', ['families_left' => '1']));

        $this->see(URL::route('store.registration.view', $regs->first()));
        $this->see(URL::route('store.registration.edit', $regs->last()));
    }

    // ── Awaiting Dusk ────────────────────────────────────────────────────────

    public function testItShowsLeftFamilyRegistrationsAsDistinct(): void
    {
        $this->markTestSkipped('Waiting for Dusk');
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);

        $registrations = factory(Registration::class, 10)->create(['centre_id' => $centre->id]);
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

    public function testItPreventsAccessToLeftFamilyRegistrations(): void
    {
        $this->markTestSkipped('Waiting for Dusk');
        $centre = factory(Centre::class)->create();
        $centreUser = $this->userInCentre($centre);

        $registrations = factory(Registration::class, 10)->create(['centre_id' => $centre->id]);
        $leavingFamily = $registrations->first()->family;
        $leavingFamily->leaving_on = Carbon::now();
        $leavingFamily->leaving_reason = config('arc.leaving_reasons')[0];
        $leavingFamily->save();

        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->check('#families_left');

        $this->assertCount(2, $this->crawler->filter('tr.inactive td.right.no-wrap div.disabled'));
        $this->assertCount(0, $this->crawler->filter('tr.inactive td.right.no-wrap div:not(.disabled)'));
        $this->assertCount(0, $this->crawler->filter('tr.active td.right.no-wrap div.disabled'));
        $this->assertCount(18, $this->crawler->filter('tr.active td.right.no-wrap div:not(.disabled)'));
    }
}
