<?php
namespace Tests\Feature\Store;

use App\Centre;
use App\Registration;
use App\CentreUser;
use App\Sponsor;
use App\Http\Controllers\Service\Admin\SponsorsController;
use InvalidArgumentException;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class RegistrationPageTest extends StoreTestCase
{
    use DatabaseMigrations;

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

    /** @test */
    public function itShowsAPrimaryCarerInput()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="pri_carer"]')
        ;
    }

    /** @test */
    public function itShowsASecondaryCarerInput()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="carer_adder_input"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAChildInputComplex()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="dob-month"]')
            ->seeElement('input[name="dob-year"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAConsentCheckbox()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[type=checkbox][name="consent"]')
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
    public function itShowsAFormSaveButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeInElement('button[type=Submit]', 'Save Family')
        ;
    }

    /** @test */
    public function itShowsALogoutButton()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->seeInElement('button[type=submit]', 'Log out')
        ;
    }

    /** @test */
    public function itShowsTheLoggedInUserDetails()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->see($this->centreUser->name)
            ->see($this->centreUser->centre->name)
        ;
    }

    /**
     * @test
     */
    public function logoDoesntRedirectMeToDashboard()
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

    /** @test */
    public function itCanSaveARegistration()
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
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

    /** @test */
    public function itRequiresConsentToSave()
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

    /** @test */
    public function itRequiresAPrimaryCarerToSave()
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

    /** @test */
    public function selectingReceivingHSPutsDateInTable()
    {
        $this->assertEquals(0, Registration::get()->count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-receiving', 'eligibility-hsbs')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::get()->count());
        $registration = Registration::first();
        $this->assertNotNull($registration->eligible_from);
    }

    /** @test */
    public function selectingNotReceivingHSPutsNullInTable()
    {
        $this->assertEquals(0, Registration::get()->count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-applying', 'eligibility-hsbs')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::get()->count());
        $registration = Registration::first();
        $this->assertNull($registration->eligible_from);
    }

    /** @test */
    public function changingToNotReceivingHSPutsNullInTable()
    {
        $this->assertEquals(0, Registration::get()->count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-receiving', 'eligibility-hsbs')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'registration' => 1 ]))
        ;
        $this->assertEquals(1, Registration::get()->count());
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

    /** @test */
    public function updatingOtherFieldsDoesNotChangeEligibiltyDate()
    {
        $this->assertEquals(0, Registration::get()->count());
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'pri_carer')
            ->check('consent')
            ->select('healthy-start-receiving', 'eligibility-hsbs')
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
}
