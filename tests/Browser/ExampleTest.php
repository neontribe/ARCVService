<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Centre;
use App\CentreUser;
use App\Evaluation;
use App\Sponsor;

class ExampleTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $sponsor;

    public function setUp() :void
    {

       parent::setUp();

       // Make a sponsor with a rule for checking kids
       $this->sponsor = factory(Sponsor::class)
           ->create();

       $eval = new Evaluation([
           "name" => "FamilyHasUnverifiedChildren",
           "value" => 0,
           "purpose" => "notices",
           "entity" => "App\Family",
           "sponsor_id" => $this->sponsor->id
       ]);
       $eval->save();
       $this->sponsor->fresh();

    }
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $centre = factory(Centre::class)->create([
            'sponsor_id' =>  $this->sponsor->id
        ]);

        // Create a CentreUser
        $centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('password_store'),
        ]);
        $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
        $this->browse(function ($browser) use ($centreUser) {

            $browser->visit('/login')
                    ->assertSee('Log In')
                    ->assertSee('Email Address')
                    ->type('email', $centreUser->email)
                    ->type('password', "password_store")
                    ->press('Log In')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Add a new Family')
                    ->click('#add-family')
                    ->assertPathIs('/registrations/create')
                    ->assertMissing('.remove_field')
                    ->type('carer_adder_input', "NEW CARER")
                    ->press('#add_collector')
                    ->screenshot('test')
                    ->assertVisible('.remove_field')
                    ->assertVisible('.dob-month')
                    ->assertVisible('.dob-year')
                    ->assertSee('ID Checked')
                    ->assertPresent('.styled-checkbox')
                    ;
        });
    }
}
