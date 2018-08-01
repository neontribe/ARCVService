<?php

namespace Tests;

use App\Centre;
use App\Registration;
use App\CentreUser;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class DashboardPageTest extends TestCase
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
            "centre_id" => $this->centre->id,
        ]);

        // Make the centre a registration
        $this->registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);
    }

    /** @test */
    public function itShowsTheExportButtonWhenUserCanExport()
    {
        // Create an FM User
        $fmuser =  factory(CentreUser::class)->create([
            "name"  => "FM test user",
            "email" => "testfmuser@example.com",
            "password" => bcrypt('test_fmuser_pass'),
            "centre_id" => $this->centre->id,
            "role" => "foodmatters_user",
        ]);

        // Create a CC user
        $ccuser =  factory(CentreUser::class)->create([
            "name"  => "CC test user",
            "email" => "testccuser@example.com",
            "password" => bcrypt('test_ccuser_pass'),
            "centre_id" => $this->centre->id,
            "role" => "centre_user",
        ]);

        $this->actingAs($ccuser)
            ->visit(URL::route('service.dashboard'))
            ->dontSee(URL::route('service.centres.registrations.summary'))
        ;

        Auth::logout();

        $this->actingAs($fmuser)
            ->visit(URL::route('service.dashboard'))
            ->see(URL::route('service.centres.registrations.summary'))
        ;
    }

    /** @test */
    public function itShowsTheLoggedInUserDetails()
    {
        $this->actingAs($this->centreUser)
            ->visit(URL::route('service.registration.edit', [ 'id' => $this->registration->id ]))
            ->see($this->centreUser->name)
            ->see($this->centreUser->centre->name)
        ;
    }

    /** @test */
    public function itShowsThePrintButtonWithReleventTextForPrintPref()
    {
        // Set centre print_pref to 'collection'.
        $this->centre->print_pref = 'collection';
        $this->centre->save();
        $this->actingAs($this->centreUser->fresh())
            ->visit(url::route('service.dashboard'))
            ->see('Print collection sheet')
            ->see(URL::route('service.centre.registrations.collection', ['id' => $this->centre->id ]))
        ;

        // Set centre print_pref to 'individual'.
        $this->centre->print_pref = 'individual';
        $this->centre->save();
        $this->actingAs($this->centreUser->fresh())
            ->visit(url::route('service.dashboard'))
            ->see('Print all family sheets')
            ->see(URL::route('service.registrations.print'))
        ;
    }
}
