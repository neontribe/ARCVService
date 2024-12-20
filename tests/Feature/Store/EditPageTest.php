<?php

namespace Tests\Feature\Store;

use Tests\StoreTestCase;
use App\Carer;
use App\Centre;
use App\Child;
use App\CentreUser;
use App\Evaluation;
use App\Family;
use App\Registration;
use App\Sponsor;
use App\Http\Controllers\Service\Admin\SponsorsController;
use App\Services\VoucherEvaluator\Evaluations\ChildIsPrimarySchoolAge;
use App\Services\VoucherEvaluator\Evaluations\FamilyHasNoEligibleChildren;
use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class EditPageTest extends StoreTestCase
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
    private $faker;
    private $scottishRulesSponsor;
    private $scottishFamily;
    private $scottishCentre;
    private $scottishCentreUser;
    private $scottishRegistration;
    private $spRulesSponsor;
    private $spFamily;
    private $spCentre;
    private $spCentreUser;
    private $spRegistration;

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

        // make centre some registrations
        $this->registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);

        $scottishRules = SponsorsController::scottishFamilyOverrides();
        $this->scottishRulesSponsor = factory(Sponsor::class)->create();
        $this->scottishRulesSponsor->evaluations()->saveMany($scottishRules);
        $this->scottishCentre = factory(Centre::class)->create([
          'sponsor_id' => $this->scottishRulesSponsor
        ]);

        // Create a CentreUser
        $this->scottishCentreUser =  factory(CentreUser::class)->create([
            "name"  => "scottish test user",
            "email" => "scottishtestuser@example.com",
            "password" => bcrypt('scottish_test_user_pass'),
        ]);
        $this->scottishCentreUser->centres()->attach($this->scottishCentre->id, ['homeCentre' => true]);
        $this->scottishFamily = factory(Family::class)->create();

        $this->scottishRegistration = factory(Registration::class)->create([
            "centre_id" => $this->scottishCentre->id,
            "family_id" => $this->scottishFamily->id,
        ]);
        // The registration factory gives the family kids, so get rid of them
        // so we can be more specific.
        $this->scottishFamily->children()->delete();

        // Social prescribing set up
        $spRules = SponsorsController::socialPrescribingOverrides();
        $this->spRulesSponsor = factory(Sponsor::class)->create([
            'programme' => 1
        ]);
        $this->spRulesSponsor->evaluations()->saveMany($spRules);
        $this->spCentre = factory(Centre::class)->create([
          'sponsor_id' => $this->spRulesSponsor->id
        ]);

        // Create a CentreUser
        $this->spCentreUser =  factory(CentreUser::class)->create([
            "name"  => "sp test user",
            "email" => "sptestuser@example.com",
            "password" => bcrypt('sp_test_user_pass'),
        ]);
        $this->spCentreUser->centres()->attach($this->spCentre->id, ['homeCentre' => true]);
        $this->spFamily = factory(Family::class)->create();

        $this->spRegistration = factory(Registration::class)->create([
            "centre_id" => $this->spCentre->id,
            "family_id" => $this->spFamily->id,
        ]);
        // The registration factory gives the family kids, so get rid of them
        // so we can be more specific.
        $this->spFamily->children()->delete();

    }

    /** @test */
    public function itShowsAPrimaryCarerInput()
    {
        $pri_carer = $this->registration->family->carers->first();
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
            ->seeElement('input[id="carer"][value="'. $pri_carer->name .'"]')
        ;
    }

    /** @test */
    public function itShowsASecondaryCarerInput()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
            ->seeElement('input[name="carer_adder_input"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAListOfSecondaryCarers()
    {
        // Clear the carers
        $this->registration->family->carers()->delete();
        // Make 4 more
        $new_carers = factory(Carer::class, 4)->make();
        // Add to Family
        $this->registration->family->carers()->saveMany($new_carers);

        // Get the carers again
        $carers = $this->registration->family->carers;

        // Knock the first one off
        $carers->shift();

        // There should be 3...
        $this->assertTrue($carers->count() == 3);

        // Find the edit page
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
        ;
        // See the names in the page
        foreach ($carers as $sec_carer) {
             $this->see($sec_carer->name)
                 ->seeElement('input[type="text"][value="'. $sec_carer->name .'"]')
                 ;
        }
    }


    /** @test */
    public function itShowsAChildInputComplex()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
            ->seeElement('input[name="dob-month"]')
            ->seeElement('input[name="dob-year"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAListOfChildren()
    {
        // Clear the children
        $this->registration->family->children()->delete();
        // Make 4 more
        $new_children = factory(Child::class, 4)->make();
        // Add to Family
        $this->registration->family->children()->saveMany($new_children);

        // Get the children again
        $children = $this->registration->family->children;

        // There should be 4...
        $this->assertTrue($children->count() == 4);

        // Find the edit page
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
        ;
        // See the names in the page
        foreach ($children as $child) {
            $this->see('<td class="age-col">'. $child->getAgeString() .'</td>')
                ->see('<td class="dob-col">'. $child->getDobAsString() .'</td>')
                ->seeElement('input[type="hidden"][value="'. $child->dob->format('Y-m') .'"]')
            ;
        }
    }

    /** @test */
    public function itShowsALogoutButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
            ->seeInElement('button[type=submit]', 'Log out')
        ;
    }

    /** @test */
    public function itShowsAFormSaveButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration ]))
            ->seeInElement('button[type=submit]', 'Save Changes')
        ;
    }

    /** @test */
    public function itShowsAnEligibilitySelect()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('#eligibility-hsbs>option[value="healthy-start-applying"][selected]')
            ->seeElement('#eligibility-hsbs>option[value="healthy-start-receiving"]')
            ->seeElement('#eligibility-hsbs>option[value="healthy-start-receiving-not-eligible-or-rejected"]')
            ->seeElement('#eligibility-nrpf>option[value="yes"]')
            ->seeElement('#eligibility-nrpf>option[value="no"]')
        ;
    }

    /** @test */
    public function itShowsTheLoggedInUserDetails()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration->id ]))
            ->see($this->centreUser->name)
            ->see($this->centreUser->centre->name)
        ;
    }

    /** @test */
    public function itShowsTheLeavingFormIfFamilyIsOnScheme()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration->id ]))
            ->see('Remove this family')
        ;
    }

    /** @test */
    public function itDoesNotShowTheLeavingFormIfFamilyHasLeftScheme()
    {
        $family = $this->registration->family;
        $family->leaving_on = Carbon::now();
        $family->leaving_reason = config('arc.leaving_reasons')[0];
        $family->save();
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->registration->id ]))
            ->dontSee('Remove this family')
        ;
    }

    /** @test */
    public function childrensDOBsGiveExpectedAge()
    {
        // Set Carbon::now to 01/01/2018
        Carbon::setTestNow(Carbon::parse('first day of January 2018')->startOfDay());

        // Create a Centre, CentreUser and Registration
        $centre = factory(Centre::class)->create([
            'sponsor_id' => 1 // Not an SP sponsor
        ]);
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "tester",
            "email" => "tester@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);

        $registration = factory(Registration::class)->create([
            'centre_id' => $centre->id,
        ]);

        // Amend the first family to have 3 children.
        $family = $registration->family;
        $family->children()->delete();

        $family->children()
            ->saveMany(
                collect([
                    factory(Child::class, 3)->state('betweenOneAndPrimarySchoolAge')->make(),
                ])->flatten()
            )
        ;

        // Amend 3 children's DOB to be 11, 12 + 13 months old.
        $family->children[0]->dob = Carbon::now()->subMonths(13)->startOfMonth()->startOfDay();
        $family->children[0]->save();
        $family->children[1]->dob = Carbon::now()->subMonths(12)->startOfMonth()->startOfDay();
        $family->children[1]->save();
        $family->children[2]->dob = Carbon::now()->subMonths(11)->startOfMonth()->startOfDay();
        $family->children[2]->save();

        // Test that entering children's DOB's gives the expected age.
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $family->id ]))
            ->see('<td class="age-col">1 yr, 1 mo</td>')
            ->see('<td class="age-col">1 yr, 0 mo</td>')
            ->see('<td class="age-col">0 yr, 11 mo</td>')
        ;

        // Set Carbon date & time back
        Carbon::setTestNow();
    }

    /** @test */
    public function itWillNotAcceptAnInvalidLeavingReason()
    {
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            ->press('Remove this family')
            ->call(
                'PUT',
                route('store.registration.family', $this->registration->family->id),
                ['leaving_reason' => 'Not a good one']
            )
        ;
        $this->assertResponseStatus(302);
        $this->assertEquals('The selected leaving reason is invalid.', $response->exception->getMessage());
    }

    /** @test */
    public function itWillRejectUpdatesIfFamilyHasLeft()
    {
        $family = $this->registration->family;
        $family->leaving_on = Carbon::now();
        $family->leaving_reason = config('arc.leaving_reasons')[0];
        $family->save();

        $data = [
            'pri_carer' => ['A String'],
            'children' => [
                0 => ['dob' => '2017-09']
            ]
        ];

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            ->call(
                'PUT',
                route('store.registration.update', $this->registration->id),
                $data
            )
        ;
        $this->assertResponseStatus(403);
    }

    /** @test */
    public function itWillRejectLeavingWithoutAReason()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            ->press('Remove this family')
            ->press('Yes')
            // Still see header of leaving popup
            ->see('Reason for leaving')
            // Still see the button - will prove that the family is still in scheme
            ->see('Remove this family')
        ;
    }

    /** @test */
    public function itWillAcceptLeavingWithAReason()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            ->press('Remove this family')
            ->select(config('arc.leaving_reasons')[0], 'leaving_reason')
            ->press('Yes')
            ->seePageIs(route('store.registration.index'))
        ;
    }

    /** @test */
    public function leaveAmountIncreases()
    {
        // Create a family
        $family = factory(Family::class)->create();
        // Make a registration for the family
        $registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
            "family_id" => $family->id,
        ]);
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $registration->id))
            ->press('Remove this family')
            ->select(config('arc.leaving_reasons')[0], 'leaving_reason')
            ->press('Yes')
            ->seePageIs(route('store.registration.index'))
        ;

        $this->seeInDatabase('families', [
            'id' => $family->id,
            'leave_amount' => 1
        ]);
    }

    /** @test */
    public function itShowsTheCorrectEvaluatingRules()
    {
        $evals = $this->registration
            ->getEvaluator()
            ->getPurposeFilteredEvaluations('credits');

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id));

        // We can see the advice box
        $this->see("The system gives these vouchers per week:");

        // The advice box has the correct number of credits in it
        $startingEvalCount = count($evals);
        $this->assertCount($startingEvalCount, $this->crawler->filter('ul#creditables li'));

        // Add a credit rule to the defaults it doesn't have
        $this->centre->sponsor->evaluations()->save(
            new Evaluation([
                'name' => 'ChildIsPrimarySchoolAge',
                'purpose' => 'credits',
                'entity' => 'App\Child',
                'value' => 4
            ])
        );
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id));

        // See it has increased by one
        $this->assertCount($startingEvalCount+1, $this->crawler->filter('ul#creditables li'));
        // See the reason
        $rule = new ChildIsPrimarySchoolAge();
        $this->see($rule->reason);

        // See a single disqualifier, that the system gets anyway
        $this->see("Reminders:");
        $this->assertCount(1, $this->crawler->filter('ul#disqualifiers li'));

        // Add a disqualifier to the defaults it doesn't have
        $this->centre->sponsor->evaluations()->save(
            new Evaluation([
                'name' => 'FamilyHasNoEligibleChildren',
                'purpose' => 'disqualifiers',
                'entity' => 'App\Family',
                'value' => 0
            ])
        );

        // Reload page
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id));

        // See another disqualifier
        $this->assertCount(2, $this->crawler->filter('ul#disqualifiers li'));

        // See the reason
        $rule = new FamilyHasNoEligibleChildren();
        $this->see($rule->reason);
    }

    /** @test */
    public function ICanSeeAScottishChildCanBeDeferred()
    {
      Config::set('arc.scottish_school_month', Carbon::now()->month + 1);
      $canDefer = factory(Child::class)->state('canDefer')->make();
      $this->scottishFamily->children()->save($canDefer);
      $inputID = "children[" . $canDefer->id . "][deferred]";
      $selector = 'input[id=\'' . $inputID . '\']';
      $this->actingAs($this->scottishCentreUser, 'store')
        ->visit(URL::route('store.registration.edit', $this->scottishRegistration->id))
        ->see('<td class="age-col">'. $canDefer->getAgeString() .'</td>')
        ->see('<td class="dob-col">'. $canDefer->getDobAsString() .'</td>')
        ->seeElement('input[type="hidden"][value="'. $canDefer->dob->format('Y-m') .'"]')
        ->seeElement($selector)
      ;
    }

    /** @test */
    public function ICanDeferAScottishChild()
    {
      Config::set('arc.scottish_school_month', Carbon::now()->month + 1);
      $canDefer = factory(Child::class)->state('canDefer')->make();
      $this->scottishFamily->children()->save($canDefer);
      $this->seeInDatabase('children', [
          'id' => $canDefer->id,
          'deferred' => 0
      ]);

      // This is what happens when you have square brackets in ids.
      $inputID = "children[" . $canDefer->id . "][deferred]";
      $selector = 'input[id=\'' . $inputID . '\']';

      $this->actingAs($this->scottishCentreUser, 'store')
        ->visit(URL::route('store.registration.edit', $this->scottishRegistration->id))
        ->see('<td class="age-col">'. $canDefer->getAgeString() .'</td>')
        ->see('<td class="dob-col">'. $canDefer->getDobAsString() .'</td>')
        ->seeElement('input[type="hidden"][value="'. $canDefer->dob->format('Y-m') .'"]')
        ->seeElement($selector)
        ->check($inputID)
        ->press('Save Changes')
        ->seePageIs(URL::route('store.registration.edit', [ 'registration' => $this->scottishRegistration->id ]))
      ;
      // Saving changes deletes the children and re-adds them,
      // so we can't use the same id. Since we only made one kid,
      // we'll need to trust that this check is fine.
      $this->seeInDatabase('children', [
          'deferred' => 1
      ]);
    }

    /** @test */
    public function itShowsAnSPRegistrationsDetailsCorrectly()
    {
        $new_carers = factory(Carer::class, 2)->make();
        $this->spRegistration->family->carers()->saveMany($new_carers);
        //
        $new_participants = factory(Child::class, 3)->make();
        $this->spRegistration->family->children()->saveMany($new_participants);
        //
        $children = $this->spRegistration->family->children;
        $this->assertTrue($children->count() === 3);
        // Find the edit page
        $this->actingAs($this->spCentreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => $this->spRegistration ]))
        ;
        // See the names in the page
        foreach ($new_carers as $new_carer) {
            $this->see($new_carer->name);
        }

        foreach ($new_participants as $new_participant) {
            $this->see('<td class="age-col">'. explode(',',$new_participant->getAgeString())[0] .'</td>');
            $this->dontSee('ID Checked');
            $this->dontSee('eligibility-hsbs');
            $this->dontSee('eligibility-nrpf');
        }
    }

    /** @test */
    public function ICanDeleteASecondaryCarerWhoHasCollectedABundle()
    {
        $this->registration->family->carers()->delete();
        $main_carer = factory(Carer::class)->make([
            'name' => 'Main Carer'
        ]);
        $this->registration->family->carers()->save($main_carer);
        $secondary_carer = factory(Carer::class)->make([
            'name' => 'Secondary Carer'
        ]);
        $this->registration->family->carers()->save($secondary_carer);
        $this->seeInDatabase('carers', [
            'name' => 'Main Carer'
        ]);
        $this->seeInDatabase('carers', [
            'name' => 'Secondary Carer'
        ]);

        //All this just to delete the carer after they've collcted a bundle.
        $currentBundle = $this->registration->currentBundle(); //Required. No idea why.
        $this->assertCount(1, $this->registration->bundles);
        $disbursementCentre = $this->centre->id;
        $disbursementDate = Carbon::now()->startOfWeek()->format("Y-m-d");
        $collectingCarer = 'Secondary Carer';
        $route = route('store.registration.voucher-manager', ['registration' => $this->registration->id]);
        $put_route = route('store.registration.vouchers.put', ['registration' => $this->registration->id]);
        $data = [
            "collected_at" => $disbursementCentre,
            "collected_on" => $disbursementDate,
            "collected_by" => $collectingCarer
        ];
        $response = $this->actingAs($this->centreUser, 'store')
            ->visit($route)
            ->put(
                $put_route,
                $data
            );

        $carer_to_delete = Carer::where('id', $secondary_carer->id)->first();
        $carer_to_delete->delete();
        $this->seeInDatabase('carers', [
            'id' => $secondary_carer->id,
            'name' => 'Deleted'
        ]);
        $this->dontSeeInDatabase('carers', [
            'id' => $secondary_carer->id,
            'deleted_at' => null
        ]);

        // We can't see the actual list showing 'Deleted' because of the way phpunit works
        // but at least check it doesn't throw an error.
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.collection-history', [ 'registration' => $this->registration ]))
            ->assertResponseStatus(200)
            ->see('Full Collection History')
        ;
    }
}
