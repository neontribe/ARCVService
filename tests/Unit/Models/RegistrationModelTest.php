<?php

namespace Tests\Unit\Models;

use App\Carer;
use App\Centre;
use App\Family;
use App\Registration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationModelTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a registration and force its primary carer to a known name.
     * The primary carer is the one with the lowest id (MIN), which is the carer
     * the Registration factory creates automatically.
     */
    private function registrationWithCarerName(Centre $centre, string $name): Registration
    {
        $reg = factory(Registration::class)->create(['centre_id' => $centre->id]);
        $reg->family->carers()->orderBy('id')->first()->update(['name' => $name]);
        return $reg->fresh();
    }

    /**
     * Build a base query scoped to a single centre with the primary carer join applied.
     * Scope tests chain from this to keep per-test setup noise out of assertions.
     */
    private function baseQuery(Centre $centre): Builder
    {
        return Registration::query()
            ->withPrimaryCarer()
            ->whereIn('registrations.centre_id', [$centre->id]);
    }

    // ── Creation ──────────────────────────────────────────────────────────────

    public function testItCanBeCreated(): void
    {
        $family = factory(Family::class)->create();
        $centre = factory(Centre::class)->create();

        $registration = new Registration();
        $registration->centre_id = $centre->id;
        $registration->eligibility_hsbs = "healthy-start-applying";
        $registration->eligibility_nrpf = "no";
        $registration->family_id = $family->id;

        $this->assertTrue($registration->save());
    }

    // ── scopeWhereActiveFamily ────────────────────────────────────────────────

    public function testItCanReturnRegistrationsOnlyForActiveFamilies(): void
    {
        // Create a centre
        $centre = factory(Centre::class)->create();

        // Create 4 random registrations (and families etc.) in that centre.
        $registrations = factory(Registration::class, 4)->create([
            'centre_id' => $centre->id,
        ]);

        // Check that we have 4.
        $this->assertSame(4, Registration::whereActiveFamily()->count());

        // A family has left.
        $family = $registrations->first()->family;
        $family->leaving_on = Carbon::now();
        $family->save();

        // check there are only 3.
        $this->assertSame(3, Registration::whereActiveFamily()->count());
    }

    // ── scopeWithPrimaryCarer ─────────────────────────────────────────────────

    public function testWithPrimaryCarerJoinsCarerWithLowestId(): void
    {
        $centre = factory(Centre::class)->create();
        $reg = factory(Registration::class)->create(['centre_id' => $centre->id]);

        // The factory creates one carer. Capture it and add a second with a higher id.
        $reg->family->carers()->orderBy('id')->first()->update(['name' => 'Primary Carer']);
        Carer::create(['name' => 'Secondary Carer', 'family_id' => $reg->family_id]);

        $result = $this->baseQuery($centre)
            ->select('registrations.*', 'carers.name as carer_name')
            ->where('registrations.id', $reg->id)
            ->first();

        $this->assertSame('Primary Carer', $result->carer_name);
    }

    public function testWithPrimaryCarerReturnsOneRowPerRegistrationRegardlessOfCarerCount(): void
    {
        $centre = factory(Centre::class)->create();

        // Each registration gets a second carer; without the MIN(id) grouping
        // a naive join would double the row count.
        $regs = factory(Registration::class, 3)->create(['centre_id' => $centre->id]);
        foreach ($regs as $reg) {
            Carer::create(
                ['name' => 'Extra Carer', 'family_id' => $reg->family_id],
                ['name' => 'Another Carer', 'family_id' => $reg->family_id],
            );
        }

        $this->assertSame(3, $this->baseQuery($centre)->count());
    }

    public function testWithPrimaryCarerMakesCarerNameAvailableForFiltering(): void
    {
        $centre = factory(Centre::class)->create();
        $reg = $this->registrationWithCarerName($centre, 'Findable Name');

        // filterByCarerName uses carers.name in its WHERE — this would throw if
        // withPrimaryCarer had not established the join first.
        $results = $this->baseQuery($centre)
            ->filterByCarerName('Findable')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame($reg->id, $results->first()->id);
    }

    // ── scopeOrderByCarerName ─────────────────────────────────────────────────

    public function testOrderByCarerNameSortsAscendingByDefault(): void
    {
        $centre = factory(Centre::class)->create();
        $charlie = $this->registrationWithCarerName($centre, 'Charlie');
        $alice = $this->registrationWithCarerName($centre, 'Alice');
        $bob = $this->registrationWithCarerName($centre, 'Bob');

        $ids = $this->baseQuery($centre)
            ->orderByCarerName()
            ->pluck('registrations.id')
            ->toArray();

        $this->assertSame([$alice->id, $bob->id, $charlie->id], $ids);
    }

    public function testOrderByCarerNameSortsDescendingWhenPassedTrue(): void
    {
        $centre = factory(Centre::class)->create();
        $charlie = $this->registrationWithCarerName($centre, 'Charlie');
        $alice = $this->registrationWithCarerName($centre, 'Alice');
        $bob = $this->registrationWithCarerName($centre, 'Bob');

        $ids = $this->baseQuery($centre)
            ->orderByCarerName(descending: true)
            ->pluck('registrations.id')
            ->toArray();

        $this->assertSame([$charlie->id, $bob->id, $alice->id], $ids);
    }

    public function testOrderByCarerNameIsCaseInsensitive(): void
    {
        $centre = factory(Centre::class)->create();
        $lower = $this->registrationWithCarerName($centre, 'bob');
        $upper = $this->registrationWithCarerName($centre, 'Alice');

        $ids = $this->baseQuery($centre)
            ->orderByCarerName()
            ->pluck('registrations.id')
            ->toArray();

        $this->assertSame([$upper->id, $lower->id], $ids);
    }

    // ── scopeFilterByCarerName ────────────────────────────────────────────────

    public function testFilterByCarerNameFiltersOutNonMatchingCarers(): void
    {
        $centre = factory(Centre::class)->create();
        $match = $this->registrationWithCarerName($centre, 'Alice Smith');
        $nomatch = $this->registrationWithCarerName($centre, 'Bob Jones');

        $ids = $this->baseQuery($centre)
            ->filterByCarerName('Smith')
            ->pluck('registrations.id')
            ->toArray();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($nomatch->id, $ids);
    }

    public function testFilterByCarerNameIsCaseInsensitive(): void
    {
        $centre = factory(Centre::class)->create();
        $reg = $this->registrationWithCarerName($centre, 'Alice SMITH');

        $results = $this->baseQuery($centre)
            ->filterByCarerName('smith')
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame($reg->id, $results->first()->id);
    }

    public function testFilterByCarerNameRanksExactMatchFirst(): void
    {
        $centre = factory(Centre::class)->create();
        $this->registrationWithCarerName($centre, 'Smith Jones'); // rank 1 — prefix
        $exact = $this->registrationWithCarerName($centre, 'Smith');   // rank 0 — exact
        $this->registrationWithCarerName($centre, 'Alice Smith'); // rank 2 — suffix

        $ids = $this->baseQuery($centre)
            ->filterByCarerName('Smith')
            ->pluck('registrations.id')
            ->toArray();

        $this->assertSame($exact->id, $ids[0]);
    }

    public function testFilterByCarerNameRanksPrefixMatchBeforeWordBoundaryMatch(): void
    {
        $centre = factory(Centre::class)->create();
        $prefix = $this->registrationWithCarerName($centre, 'Smith Alice'); // LIKE 'Smith %' → rank 1
        $boundary = $this->registrationWithCarerName($centre, 'Alice Smith'); // LIKE '% Smith' → rank 2

        $ids = $this->baseQuery($centre)
            ->filterByCarerName('Smith')
            ->pluck('registrations.id')
            ->toArray();

        $this->assertSame($prefix->id, $ids[0]);
        $this->assertSame($boundary->id, $ids[1]);
    }

    public function testFilterByCarerNameRanksWordBoundaryMatchBeforeInternalMatch(): void
    {
        $centre = factory(Centre::class)->create();
        $internal = $this->registrationWithCarerName($centre, 'Jones Smithfield'); // rank 3 — internal
        $boundary = $this->registrationWithCarerName($centre, 'Alice Smith');      // rank 2 — suffix

        $ids = $this->baseQuery($centre)
            ->filterByCarerName('Smith')
            ->pluck('registrations.id')
            ->toArray();

        $this->assertSame($boundary->id, $ids[0]);
        $this->assertSame($internal->id, $ids[1]);
    }

    public function testFilterByCarerNameReturnsNoResultsWhenNothingMatches(): void
    {
        $centre = factory(Centre::class)->create();
        $this->registrationWithCarerName($centre, 'Alice Jones');
        $this->registrationWithCarerName($centre, 'Bob Williams');

        $results = $this->baseQuery($centre)
            ->filterByCarerName('XyzNoMatch')
            ->get();

        $this->assertCount(0, $results);
    }
}
