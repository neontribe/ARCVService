<?php

namespace Tests\Feature\Store;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Sponsor;
use HighSolutions\LaravelSearchy\Facades\Searchy;
use Mockery;
use Tests\MysqlStoreTestCase;
use URL;

/**
 * Tests for RegistrationController::index() when the fetchFuzzy strategy is
 * selected (MySQL driver + non-empty family_name + fuzzy=1).
 *
 * fetchFuzzy is private, so it is exercised indirectly through index().
 * Searchy is mocked so tests control relevance ranking directly without
 * needing a real full-text index.
 *
 * Requires a MySQL database. The parent class handles skipping when unavailable.
 */
class RegistrationControllerFuzzyTest extends MysqlStoreTestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function userInCentre(Centre $centre): CentreUser
    {
        $user = factory(CentreUser::class)->create();
        $user->centres()->attach($centre->id, ['homeCentre' => true]);
        return $user;
    }

    private function setCarerName(Registration $reg, string $name): void
    {
        $reg->family->carers()->orderBy('id')->first()->update(['name' => $name]);
    }

    /**
     * Mock Searchy's fluent chain to return the provided stdClass results array.
     * Each element must have a `family_id` property.
     */
    private function mockSearchy(array $results): void
    {
        $chain = Mockery::mock();
        $chain->shouldReceive('fields')->atLeast()->once()->andReturnSelf();
        $chain->shouldReceive('query')->atLeast()->once()->andReturnSelf();
        $chain->shouldReceive('get')->atLeast()->once()->andReturn($results);

        Searchy::shouldReceive('search')->with('carers')->atLeast()->once()->andReturn($chain);
    }

    // ── Strategy selection ────────────────────────────────────────────────────

    public function testIndexUsesFuzzyPathWhenFuzzySetAndDriverIsMysql(): void
    {
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);
        $reg = factory(Registration::class)->create(['centre_id' => $centre->id]);

        $this->mockSearchy([(object)['family_id' => $reg->family_id]]);

        $name = $reg->family->carers()->orderBy('id')->first()->name;

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => $name,
                'fuzzy' => '1',
            ]));

        // Searchy mock enforces it was called (once assertions in mockSearchy).
        // Registration visible confirms the fuzzy path completed successfully.
        $this->see(URL::route('store.registration.edit', $reg));
    }

    // ── Relevance ordering ────────────────────────────────────────────────────

    public function testFuzzyResultsReturnedInSearchyRelevanceOrder(): void
    {
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);

        $regs = factory(Registration::class, 3)->create(['centre_id' => $centre->id]);
        $this->setCarerName($regs[0], 'Smith Charlie');
        $this->setCarerName($regs[1], 'Smith Alice');
        $this->setCarerName($regs[2], 'Smith Bob');

        // Searchy ranks: reg[2] first, reg[0] second, reg[1] third
        $this->mockSearchy([
            (object)['family_id' => $regs[2]->family_id],
            (object)['family_id' => $regs[0]->family_id],
            (object)['family_id' => $regs[1]->family_id],
        ]);

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => 'Smith',
                'fuzzy' => '1',
            ]));

        $this->seeInElementAtPos('td.pri_carer', 'Smith Bob', 0);
        $this->seeInElementAtPos('td.pri_carer', 'Smith Charlie', 1);
        $this->seeInElementAtPos('td.pri_carer', 'Smith Alice', 2);
    }

    public function testFuzzyResultsReversedWhenDirectionIsDesc(): void
    {
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);

        $regs = factory(Registration::class, 3)->create(['centre_id' => $centre->id]);
        $this->setCarerName($regs[0], 'Smith Charlie');
        $this->setCarerName($regs[1], 'Smith Alice');
        $this->setCarerName($regs[2], 'Smith Bob');

        // Searchy ranks: reg[2] (rank 0), reg[0] (rank 1), reg[1] (rank 2)
        $this->mockSearchy([
            (object)['family_id' => $regs[2]->family_id],
            (object)['family_id' => $regs[0]->family_id],
            (object)['family_id' => $regs[1]->family_id],
        ]);

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => 'Smith',
                'fuzzy' => '1',
                'direction' => 'desc',
            ]));

        // Descending inverts Searchy rank: reg[1] (rank 2), reg[0] (rank 1), reg[2] (rank 0)
        $this->seeInElementAtPos('td.pri_carer', 'Smith Alice', 0);
        $this->seeInElementAtPos('td.pri_carer', 'Smith Charlie', 1);
        $this->seeInElementAtPos('td.pri_carer', 'Smith Bob', 2);
    }

    // ── Centre permission scoping ─────────────────────────────────────────────

    public function testFuzzyExcludesFamiliesOutsidePermittedCentres(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $myCentre = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $otherCentre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ]);

        $user = $this->userInCentre($myCentre);
        $myReg = factory(Registration::class)->create(['centre_id' => $myCentre->id]);
        $otherReg = factory(Registration::class)->create(['centre_id' => $otherCentre->id]);

        // Searchy returns both; only myReg is within permitted centres
        $this->mockSearchy([
            (object)['family_id' => $myReg->family_id],
            (object)['family_id' => $otherReg->family_id],
        ]);

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => 'test',
                'fuzzy' => '1',
            ]));

        $this->see(URL::route('store.registration.edit', $myReg));
        $this->dontSee(URL::route('store.registration.edit', $otherReg));
    }

    public function testFuzzyPreservesRelevanceOrderAfterPermissionFiltering(): void
    {
        $sponsor = factory(Sponsor::class)->create();
        $myCentre = factory(Centre::class)->create(['sponsor_id' => $sponsor->id]);
        $otherCentre = factory(Centre::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ]);

        $user = $this->userInCentre($myCentre);

        $regs = factory(Registration::class, 2)->create(['centre_id' => $myCentre->id]);
        $alien = factory(Registration::class)->create(['centre_id' => $otherCentre->id]);

        $this->setCarerName($regs[0], 'Smith Alice');
        $this->setCarerName($regs[1], 'Smith Bob');

        // Searchy ranks: alien (rank 0), regs[1] (rank 1), regs[0] (rank 2)
        // After permission filtering: regs[1] should stay ahead of regs[0]
        $this->mockSearchy([
            (object)['family_id' => $alien->family_id],
            (object)['family_id' => $regs[1]->family_id],
            (object)['family_id' => $regs[0]->family_id],
        ]);

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => 'Smith',
                'fuzzy' => '1',
            ]));

        $this->seeInElementAtPos('td.pri_carer', 'Smith Bob', 0);
        $this->seeInElementAtPos('td.pri_carer', 'Smith Alice', 1);
    }

    // ── Pagination redirect ───────────────────────────────────────────────────

    public function testFuzzyRedirectsToLastPageWhenRequestedPageExceedsLastPage(): void
    {
        $centre = factory(Centre::class)->create();
        $user = $this->userInCentre($centre);
        $regs = factory(Registration::class, 35)->create(['centre_id' => $centre->id]);

        $this->mockSearchy(
            $regs->map(function ($r) {
                return (object)['family_id' => $r->family_id];
            })->all()
        );

        $this->actingAs($user, 'store')
            ->visit(URL::route('store.registration.index', [
                'family_name' => 'test',
                'fuzzy' => '1',
                'page' => '99',
            ]));

        $this->seePageIs(URL::route('store.registration.index', [
            'family_name' => 'test',
            'fuzzy' => '1',
            'page' => '4',
        ]));
    }
}
