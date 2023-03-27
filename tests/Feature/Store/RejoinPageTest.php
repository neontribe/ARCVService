<?php

namespace Tests\Feature\Store;

use Tests\StoreTestCase;
use App\Centre;
use App\Child;
use App\CentreUser;
use App\Family;
use App\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class RejoinPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /**
     * @var Centre $centre
     * @var CentreUser $centreUser
     * @var Registration $registration
     */
    private $centre;
    private $centreUser;
    private $registration;
    private $family;
    private $children;

    public function setUp(): void
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a CentreUser
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Create a Family who left a month ago
        $this->family = factory(Family::class)->create([
            "leaving_on" => Carbon::now()->subDays(30),
            "leaving_reason" => config('arc.leaving_reasons')[0],
        ]);

        // Add 3 Children
        $this->children = factory(Child::class, 3)->make();
        $this->family->children()->saveMany($this->children);

        // Make a registration for the family
        $this->registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
            "family_id" => $this->family->id,
        ]);
    }

    /** @test */
    public function itShowsTheCorrectViewFields()
    {
        $pri_carer = $this->registration->family->carers->first();
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.view', [ 'registration' => $this->registration ]))
            ->see($pri_carer->name)
            ->see('This family has 3 children and left the project on ' . $this->family->leaving_on->format('d-m-Y'))
        ;
    }

    /** @test */
    public function itDoesNotShowTheChildrenNames()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.view', [ 'registration' => $this->registration ]))
        ;
        // Don't see the names of children in the page
        foreach ($this->children as $child) {
            $this->dontSee('<td class="age-col">'. $child->getAgeString() .'</td>')
                ->dontSee('<td class="dob-col">'. $child->getDobAsString() .'</td>')
                ->dontSeeInElement('input[type="hidden"]', $child->dob->format('Y-m'))
            ;
        }
    }

    /** @test */
    public function itShowsARejoinButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.view', [ 'registration' => $this->registration ]))
            ->see('Rejoin')
        ;
    }

    /** @test */
    public function itDoesNotShowRemoveThisFamilyButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.view', [ 'registration' => $this->registration ]))
            ->dontSee('Remove this family')
        ;
    }

    /** @test */
    public function itDoesNotShowASecondaryCarerInput()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.view', [ 'registration' => $this->registration ]))
            ->dontSeeElement('input[name="carer_adder_input"]')
            ->dontSeeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itWillAllowFamilyToRejoin()
    {
        $knownDate = Carbon::create(2023, 1, 13, 12);
        Carbon::setTestNow($knownDate);
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.view', $this->registration->id))
            ->press('Rejoin')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => $this->registration]));

        $this->seeInDatabase('families', [
            'id' => $this->family->id,
            'rejoin_on' => $knownDate
            ]);

        Carbon::setTestNow();
    }
}
