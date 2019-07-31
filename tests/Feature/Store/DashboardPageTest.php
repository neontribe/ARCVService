<?php

namespace Tests;

use App\Centre;
use App\Registration;
use App\CentreUser;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class DashboardPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /**
     * @var Centre $centre
     * @var CentreUser $centreUser
     * @var CentreUser $fmUser
     * @var Registration $registration
     */
    private $centre;
    private $centreUser;
    private $fmUser;
    private $registration;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Create a CentreUser
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "CC test user",
            "email" => "testccuser@example.com",
            "password" => bcrypt('test_ccuser_pass'),
            "role" => "centre_user",
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->fmUser = factory(CentreUser::class)->create([
            "name"  => "FM test user",
            "email" => "testfmuser@example.com",
            "password" => bcrypt('test_fmuser_pass'),
            "role" => "foodmatters_user",
        ]);
        $this->fmUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Make the centre a registration
        $this->registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id,
        ]);
    }

    /** @test */
    public function itShowsTheExportButtonsAccordingToUserRole()
    {
        // Get FM User
        $fmuser = $this->fmUser;

        // Get CC user
        $ccuser = $this->centreUser;

        $this->actingAs($ccuser, 'store')
            ->visit(URL::route('store.dashboard'))
            ->dontSee(URL::route('store.centres.registrations.summary'))
            ->dontSee(URL::route('store.vouchers.mvl.export'))
            ->see("print-registrations")
        ;

        Auth::logout();

        $this->actingAs($fmuser, 'store')
            ->visit(URL::route('store.dashboard'))
            ->see(URL::route('store.centres.registrations.summary'))
            ->see(URL::route('store.vouchers.mvl.export'))
            ->dontSee("print-registrations")
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
    public function itShowsThePrintButtonWithReleventTextForPrintPref()
    {
        // Set centre print_pref to 'collection'.
        $this->centre->print_pref = config('arc.print_preferences.0');
        $this->centre->save();
        $this->actingAs($this->centreUser->fresh(), 'store')
            ->visit(url::route('store.dashboard'))
            ->see('Print collection sheet')
            ->see(URL::route('store.centre.registrations.collection', ['id' => $this->centre->id ]))
        ;

        // Set centre print_pref to 'individual'.
        $this->centre->print_pref = config('arc.print_preferences.1');
        $this->centre->save();
        $this->actingAs($this->centreUser->fresh(), 'store')
            ->visit(url::route('store.dashboard'))
            ->see('Print all family sheets')
            ->see(URL::route('store.registrations.print'))
        ;
    }
}
