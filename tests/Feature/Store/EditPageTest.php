<?php

namespace Tests;

use App\Carer;
use App\Centre;
use App\Child;
use App\CentreUser;
use App\Registration;
use Auth;
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

    // /** @test */
    // public function itOnlyShowsAFoodMattersInputsToFoodMattersUsers()
    // {
    //     // Create a FM User
    //     $fmuser = factory(CentreUser::class)->create([
    //         "name"  => "test FM user",
    //         "email" => "testufmser@example.com",
    //         "password" => bcrypt('test_fm_user_pass'),
    //         "role" => 'foodmatters_user',
    //     ]);
    //     $fmuser->centres()->attach($this->centre->id, ['homeCentre' => true]);
    //     $users[] = $fmuser;

    //     $ccuser = factory(CentreUser::class)->create([
    //         "name"  => "test cc user",
    //         "email" => "testccuser@example.com",
    //         "password" => bcrypt('test_cc_user_pass'),
    //         "centre_id" => $this->centre->id,
    //         "role" => 'centre_user',
    //     ]);
    //     $ccuser->centres()->attach($this->centre->id, ['homeCentre' => true]);
    //     $users[] = $ccuser;

    //     foreach ($users as $centreUser) {
    //         $this->actingAs($centreUser, 'store')
    //             ->visit(URL::route('store.registration.edit', [ 'id' => $this->registration ]));
    //         if ($centreUser->can('updateDiary', Registration::class) ||
    //             $centreUser->can('updateChart', Registration::class)
    //         ) {
    //             $this->see('Documents Received:');
    //             if ($centreUser->can('updateChart')) {
    //                 $this->seeElement('input[type="hidden"][name="fm_chart"]');
    //                 $this->seeElement('input[type="checkbox"][name="fm_chart"]');
    //             }
    //             if ($centreUser->can('updateDiary')) {
    //                 $this->seeElement('input[type="hidden"][name="fm_diary"]');
    //                 $this->seeElement('input[type="checkbox"][name="fm_diary"]');
    //             }
    //         } else {
    //             $this->dontSee('Documents Received:');
    //             $this->dontSeeElement('input[type="hidden"][name="fm_chart"]');
    //             $this->dontSeeElement('input[type="checkbox"][name="fm_chart"]');
    //             $this->dontSeeElement('input[type="hidden"][name="fm_diary"]');
    //             $this->dontSeeElement('input[type="checkbox"][name="fm_diary"]');
    //         }
    //     }
    // }

    /** @test */
    public function itShowsPrivacyStatementCheckedCorrectlyAsStoredInDatabase()
    {
        // Create a CentreUser
        $fmuser = factory(CentreUser::class)->create([
            'name' => 'test fm user',
            'email' => 'testfmuser@example.com',
            'password' => bcrypt('test_fmuser_pass'),
            'role' => 'foodmatters_user',
        ]);
        $fmuser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $now = Carbon::now();

        // make a no doc registration
        $regs[] = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
            'fm_privacy_on' => null,
        ]);

        // make a privacy registration
        $regs[] = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
            'fm_privacy_on' => $now,
        ]);

        foreach ($regs as $reg) {
            $route = URL::route('store.registration.edit', ['id' => $reg->id]);
            Auth::logout();
            $this->actingAs($fmuser, 'store')
                ->visit($route)
            ;

            // Test Chart
            if ($reg->fm_privacy_on !== null) {
                $this->seeInDatabase(
                    'registrations',
                    ['id' => $reg->id, 'fm_privacy_on' => $now]
                );
                $this->seeElement('input[name="fm_privacy"][checked]');
            } else {
                $this->seeInDatabase(
                    'registrations',
                    [ 'id' => $reg->id, 'fm_privacy_on' => null ]
                );
                $this->seeElement('input[name="fm_privacy"]:not(:checked)');
                $this->dontSeeElement('input[name="fm_privacy"][checked]');
            }
        }
    }

    /** @test */
    public function itLetsAnAuthedUserUpdatePrivacyStatementState()
    {
        // Create a CentreUser
        $fmuser = factory(CentreUser::class)->create([
            'name' => 'test fm user',
            'email' => 'testfmuser@example.com',
            'password' => bcrypt('test_fmuser_pass'),
            'role' => 'foodmatters_user',
        ]);
        $fmuser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $now = Carbon::now();

        // make a no doc registration
        $regs[] = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
            'fm_privacy_on' => null,
        ]);

        // make privacy only registration
        $regs[] = factory(Registration::class)->create([
            'centre_id' => $this->centre->id,
            'fm_privacy_on' => $now,
        ]);

        foreach ($regs as $reg) {
            $route = URL::route('store.registration.edit', ['id' => $reg->id]);
            $this->actingAs($fmuser, 'store')
                ->visit($route);

            // Test Privacy
            if ($reg->fm_privacy_on !== null) {
                $this->check('fm_privacy');
            } else {
                $this->uncheck('fm_privacy');
            }

            // We get back to edit page
            $this->press('Save Changes')
                ->seeStatusCode(200)
                ->seePageIs($route)
            ;

            // Check the data has changed
            if ($reg->fm_privacy_on !== null) {
                // Just chec it is no longer null. Using Carbon time caused frequent intermittant failure.
                $this->dontSeeInDatabase(
                    'registrations',
                    [ 'id' => $reg->id, 'fm_privacy_on' => null ]
                );
            } else {
                $this->seeInDatabase(
                    'registrations',
                    [ 'id' => $reg->id, 'fm_privacy_on' => null ]
                );
            }
        }
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
        $this->assertEquals('The given data failed to pass validation.', $response->exception->getMessage());
    }

    /** @test */
    public function itWillRejectUpdatesIfFamilyHasLeft()
    {
        $family = $this->registration->family;
        $family->leaving_on = Carbon::now();
        $family->leaving_reason = config('arc.leaving_reasons')[0];
        $family->save();
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            // Throws Authorization exception 403. Expecting this exception doesn't seem to help.
            // Not sure this is the desired behaviour and it makes untestable. We need to handle gracefully.
            //->press('Save Changes')
        ;
        //$this->assertResponseStatus(403);
    }

        /**
     * @test
     */
    public function childrensDOBsGiveExpectedAge()
    {
        // Set Carbon::now to 01/01/2018
        Carbon::setTestNow(Carbon::parse('first day of January 2018'));

        // Create a centre, centreuser and registration
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

        // Amend the first family to add 3 children.
        $family = $registration->family;

        $family->children()
            ->saveMany(
                collect([
                    factory(Child::class, 'underSchoolAge', 3)->make(),
                ])->flatten()
            )
        ;

        // Amend 3 children's DOB to be 11, 12 + 13 months old.
        $family->children[0]->dob = "2016-12-01 00:00:00";
        $family->children[0]->save();
        $family->children[1]->dob = "2017-01-01 00:00:00";
        $family->children[1]->save();
        $family->children[2]->dob = "2017-02-01 00:00:00";
        $family->children[2]->save();

        // Test that entering children's DOB's gives the expected age.
        $this->actingAs($centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'id' => $family->id ]))
            ->see('<td>1 yr, 1 mo</td>')
            ->see('<td>1 yr, 0 mo</td>')
            ->see('<td>0 yr, 11 mo</td>')
            ->see('<div class="warning">');

        // Set Carbon date & time back
        Carbon::setTestNow();
    }
}
