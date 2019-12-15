<?php

namespace Tests;

use App\Carer;
use App\Centre;
use App\Child;
use App\CentreUser;
use App\Registration;
use Carbon\Carbon;
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

    public function setUp()
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
    }

    /** @test */
    public function itShowsAPrimaryCarerInput()
    {
        $pri_carer = $this->registration->family->carers->first();
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
            ->seeElement('input[id="carer"][value="'. $pri_carer->name .'"]')
        ;
    }

    /** @test */
    public function itShowsASecondaryCarerInput()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
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
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
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
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
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
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
        ;
        // See the names in the page
        foreach ($children as $child) {
            $this->see('<td>'. $child->getAgeString() .'</td>')
                ->see('<td>'. $child->getDobAsString() .'</td>')
                ->seeElement('input[type="hidden"][value="'. $child->dob->format('Y-m') .'"]')
            ;
        }
    }

    /** @test */
    public function itShowsALogoutButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
            ->seeInElement('button[type=submit]', 'Log out')
        ;
    }

    /** @test */
    public function itShowsAFormSaveButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]))
            ->seeInElement('button[type=submit]', 'Save Changes')
        ;
    }

    /** @test */
    public function itShowsTheLoggedInUserDetails()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration->id ]))
            ->see($this->centreUser->name)
            ->see($this->centreUser->centre->name)
        ;
    }

    /** @test */
    public function itShowsTheLeavingFormIfFamilyIsOnScheme()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
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
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            ->dontSee('Remove this family')
        ;
    }

    /** @test */
    public function childrensDOBsGiveExpectedAge()
    {
        // Set Carbon::now to 01/01/2018
        Carbon::setTestNow(Carbon::parse('first day of January 2018')->startOfDay());

        // Create a Centre, CentreUser and Registration
        $centre = factory(Centre::class)->create();
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
                    factory(Child::class, 'underSchoolAge', 3)->make(),
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
            ->visit(URL::route('store.registration.edit', [ 'id' => $family->id ]))
            ->see('<td>1 yr, 1 mo</td>')
            ->see('<td>1 yr, 0 mo</td>')
            ->see('<td>0 yr, 11 mo</td>')
        ;

        // Set Carbon date & time back
        Carbon::setTestNow();
    }
}
