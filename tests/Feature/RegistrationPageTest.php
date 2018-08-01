<?php

use App\Centre;
use App\Registration;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RegistrationPageTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var Centre $centre
     * @var User $user
     */
    private $centre;
    private $user;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a User
        $this->user =  factory(User::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),
            "centre_id" => $this->centre->id,
        ]);
    }

    /** @test */
    public function itShowsAPrimaryCarerInput()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeElement('input[name="carer"]')
        ;
    }

    /** @test */
    public function itShowsASecondaryCarerInput()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeElement('input[name="carer_adder_input"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAChildInputComplex()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeElement('input[name="dob-month"]')
            ->seeElement('input[name="dob-year"]')
            ->seeElement('button[id="add-dob"]')
        ;
    }

    /** @test */
    public function itShowsAConsentCheckbox()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeElement('input[type=checkbox][name="consent"]')
        ;
    }

    /** @test */
    public function itShowsAnEligabilityRadioGroup()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeElement('input[type=radio][id="healthy-start"][checked]')
            ->seeElement('input[type=radio][id="other"]')
        ;
    }

    /** @test */
    public function itShowsAFormSaveButton()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeInElement('button[type=Submit]', 'Save Family')
        ;
    }

    /** @test */
    public function itShowsALogoutButton()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->seeInElement('button[type=submit]', 'Log out')
        ;
    }

    /** @test */
    public function itShowsTheLoggedInUserDetails()
    {
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->see($this->user->name)
            ->see($this->user->centre->name)
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
        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->click('logo')
            ->seePageIs(URL::route('service.registration.create'));
    }

    /** @test */
    public function itCanSaveARegistration()
    {
        // There are no registrations
        $this->assertEquals(0, Registration::get()->count());

        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->type('Test Carer', 'carer')
            ->check('consent')
            ->press('Save Family')
            ->seePageIs(URL::route('service.registration.edit', [ 'id' => 1 ]))
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

        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->type('Test Carer', 'carer')
            ->press('Save Family')
            ->seePageIs(URL::route('service.registration.create'))
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

        $this->actingAs($this->user)
            ->visit(URL::route('service.registration.create'))
            ->check('consent')
            ->press('Save Family')
            ->seePageIs(URL::route('service.registration.create'))
            ->seeInElement(
                '#carer-span',
                'This field is required'
            )
        ;

        // There is still not a Registration.
        $this->assertEquals(0, Registration::get()->count());
    }
}
