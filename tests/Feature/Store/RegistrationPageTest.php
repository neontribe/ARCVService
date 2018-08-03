<?php

use App\Centre;
use App\Registration;
use App\CentreUser;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RegistrationPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /**
     * @var Centre $centre
     * @var CentreUser $centreUser
     */
    private $centre;
    private $centreUser;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a CentreUser
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $this->centre->id,
        ]);
    }

    /** @test */
    public function itShowsAPrimaryCarerInput()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="carer"]')
        ;
    }

    /** @test */
    public function itShowsASecondaryCarerInput()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="carer_adder_input"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAChildInputComplex()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[name="dob-month"]')
            ->seeElement('input[name="dob-year"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAConsentCheckbox()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[type=checkbox][name="consent"]')
        ;
    }

    /** @test */
    public function itShowsAnEligabilityRadioGroup()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeElement('input[type=radio][id="healthy-start"][checked]')
            ->seeElement('input[type=radio][id="other"]')
        ;
    }

    /** @test */
    public function itShowsAFormSaveButton()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeInElement('button[type=Submit]', 'Save Family')
        ;
    }

    /** @test */
    public function itShowsALogoutButton()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->seeInElement('button[type=submit]', 'Log out')
        ;
    }

    /** @test */
    public function itShowsTheLoggedInUserDetails()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->see($this->centreUser->name)
            ->see($this->centreUser->centre->name)
        ;
    }

    /**
     * @expectedException InvalidArgumentException
     * @test
     */
    public function logoDoesntRedirectMeToDashboard()
    {
        // Create some centres
        factory(App\Centre::class, 4)->create();

        //Test that clicking on a (non)link throws an Error
        //and remains on the registration page.
        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->click('logo')
            ->seePageIs(URL::route('store.registration.create'));
    }

    /** @test */
    public function itCanSaveARegistration()
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'carer')
            ->check('consent')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.edit', [ 'id' => 1 ]))
        ;

        // There is now a Registration.
        $this->assertEquals(1, Registration::get()->count());

        $registration =  Registration::find(1);

        $this->assertNotNull($registration->consented_on);
        $this->assertNotNull($registration->eligibility);
        $this->assertNotNull($registration->family);
        $this->assertNotNull($registration->family->carers);
        $this->assertEquals('Test Carer', $registration->family->carers->first()->name);
    }

    /** @test */
    public function itRequiresConsentToSave()
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->type('Test Carer', 'carer')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.create'))
            ->seeInElement(
                '#privacy-statement-span',
                'Privacy Statement must be signed in order to complete registration'
            )
        ;

        // There is still not a Registration.
        $this->assertEquals(0, Registration::get()->count());
    }

    /** @test */
    public function itRequiresAPrimaryCarerToSave()
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->centreUser)
            ->visit(URL::route('store.registration.create'))
            ->check('consent')
            ->press('Save Family')
            ->seePageIs(URL::route('store.registration.create'))
            ->seeInElement(
                '#carer-span',
                'This field is required'
            )
        ;

        // There is still not a Registration.
        $this->assertEquals(0, Registration::get()->count());
    }
}
