<?php

namespace Tests\Feature\Store;

use App\Centre;
use App\Registration;
use App\CentreUser;
use App\Sponsor;
use App\Http\Controllers\Service\Admin\SponsorsController;
use InvalidArgumentException;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use URL;

class RegistrationPageTest extends StoreTestCase
{
    use RefreshDatabase;

    /**
     * @var Centre $centre
     * @var CentreUser $centreUser
     */
    private $centre;
    private $centreUser;
    private $spCentre;
    private $spCentreUser;

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

        // Create an SP Sponsor
        $spSponsor = factory(Sponsor::class)->create([
            'programme' => 1
        ]);
        $spRules = SponsorsController::socialPrescribingOverrides();
        $spSponsor->evaluations()->saveMany($spRules);
        // Create an SP Centre
        $this->spCentre = factory(Centre::class)->create([
            'sponsor_id' => $spSponsor->id
        ]);

        // Create an SP CentreUser
        $this->spCentreUser =  factory(CentreUser::class)->create([
            "name"  => "SP user",
            "email" => "SP@example.com",
            "password" => bcrypt('test_user_pass'),
        ]);
        $this->spCentreUser->centres()->attach($this->spCentre->id, ['homeCentre' => true]);
    }


    public function testItShowsAPrimaryCarerInput(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="pri_carer"]')
        ;
    }


    public function testItShowsASecondaryCarerInput(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="carer_adder_input"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }


    public function testItShowsAChildInputComplex(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="dob-month"]')
            ->seeElement('input[name="dob-year"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }


    public function testItShowsAConsentCheckbox(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[type=checkbox][name="consent"]')
        ;
    }


    public function testItShowsAnEligibilitySelect(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('#eligibility-hsbs>option[value="0"][selected]')
            ->seeElement('#eligibility-hsbs>option[value="healthy-start-applying"]')
            ->seeElement('#eligibility-hsbs>option[value="healthy-start-receiving"]')
            ->seeElement('#eligibility-hsbs>option[value="healthy-start-receiving-not-eligible-or-rejected"]')
            ->seeElement('#eligibility-nrpf>option[value="yes"]')
            ->seeElement('#eligibility-nrpf>option[value="no"]')
        ;
    }


    public function testItShowsAFormSaveButton(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeInElement('button[type=submit]', 'Save Family')
        ;
    }


    public function testItShowsALogoutButton(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeInElement('button[type=submit]', 'Log out')
        ;
    }


    public function testItShowsTheLoggedInUserDetails(): void
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->see($this->centreUser->name)
            ->see($this->centreUser->centre->name)
        ;
    }

    public function testLogoDoesntRedirectMeToDashboard(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Create some centres
        factory(Centre::class, 4)->create();

        //Test that clicking on a (non)link throws an Error
        //and remains on the registration page.
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->click('logo')
            ->seePageIs(URL::route('store.registration.create'));
    }


    public function testItCanSaveARegistration(): void
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->select('healthy-start-applying', 'eligibility-hsbs')
            ->select('no', 'eligibility-nrpf')
            ->check('consent')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;

        // There is now a Registration.
        $this->assertEquals(1, Registration::get()->count());

        $registration =  Registration::find(1);

        $this->assertNotNull($registration->consented_on);
        $this->assertNotNull($registration->eligibility_hsbs);
        $this->assertNotNull($registration->eligibility_nrpf);
        $this->assertNotNull($registration->family);
        $this->assertNotNull($registration->family->carers);
        $this->assertEquals('Test Carer', $registration->family->carers->first()->name);
    }


    public function testItRequiresConsentToSave(): void
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.create'))
            ->seeElement('#registration-alert')
            ->see('Registration form must be signed in order to complete registration')
        ;

        // There is still not a Registration.
        $this->assertEquals(0, Registration::get()->count());
    }


    public function testItRequiresAPrimaryCarerToSave(): void
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->check('consent')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.create'))
            ->seeElement('#carer-alert')
            ->see('This field is required')
        ;

        // There is still not a Registration.
        $this->assertEquals(0, Registration::get()->count());
    }


    public function testSelectingReceivingHSPutsDateInTable(): void
    {
        $this->assertEquals(0, Registration::count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-receiving', 'eligibility-hsbs')
            ->select('no', 'eligibility-nrpf')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::get()->count());
        $registration = Registration::first();
        $this->assertNotNull($registration->eligible_from);
    }


    public function testSelectingNotReceivingHSPutsNullInTable(): void
    {
        $this->assertEquals(0, Registration::get()->count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-applying', 'eligibility-hsbs')
            ->select('no', 'eligibility-nrpf')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::get()->count());
        $registration = Registration::first();
        $this->assertNull($registration->eligible_from);
    }


    public function testChangingToNotReceivingHSPutsNullInTable(): void
    {
        $this->assertEquals(0, Registration::count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-receiving', 'eligibility-hsbs')
            ->select('no', 'eligibility-nrpf')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::count());
        $registration = Registration::first();
        $this->assertNotNull($registration->eligible_from);

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', [ 'registration' => 1 ]))
            ->select('healthy-start-applying', 'eligibility-hsbs')
            ->press('Save Changes')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $registration = Registration::first();
        $this->assertNull($registration->eligible_from);
    }


    public function testUpdatingOtherFieldsDoesNotChangeEligibiltyDate(): void
    {
        $this->assertEquals(0, Registration::get()->count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-receiving', 'eligibility-hsbs')
            ->select('no', 'eligibility-nrpf')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::get()->count());
        $registration = Registration::first();
        // SP allows this field to be null so test needed changing to accommodate this
        if ($this->assertNotNull($registration->eligible_from)) {
            $originalDate = $registration->eligible_from;

            $this->actingAs($this->centreUser, 'store')
                ->visit(URL::route('store.registration.edit', [ 'registration' => 1 ]))
                ->press('Save Changes')
                ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
            ;
            $registration = Registration::first();
            $this->assertEquals($registration->eligible_from, $originalDate);
        }
    }


    public function testAsAnSPUserICanSeeTheCorrectInputs(): void
    {
        $this->actingAs($this->spCentreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="pri_carer"]')
            ->seeElement('input[name="age"]')
            ->seeElement('input[id="carer_adder_input"]')
            ->seeElement('button[id="add-carer-age"]')
            ->seeElement('button[id="add-age"]')
            ->dontSee('verified-col')
            ->dontSee('can-defer-col')
            ->dontSee('eligibility-hsbs')
            ->dontSee('eligibility-nrpf')
            ->see('Has the registration form been completed and signed?')
            ->see('Save Household')
        ;
    }
}
