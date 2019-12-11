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
     * @var CentreUser $downloadUser
     * @var CentreUser $fmUser
     * @var Registration $registration
     */
    private $centre;
    private $centreUser;
    private $downloadUser;
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
        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        // Create a CentreUser
        $this->downloadUser = factory(CentreUser::class, 'withDownloader')->create([
            "name"  => "CC DL user",
            "email" => "testccdluser@example.com",
            "password" => bcrypt('test_ccdluser_pass'),
        ]);
        $this->downloadUser->centres()->attach($this->centre->id, ['homeCentre' => true]);

        $this->fmUser = factory(CentreUser::class, 'FMUser')->create([
            "name"  => "FM test user",
            "email" => "testfmuser@example.com",
            "password" => bcrypt('test_fmuser_pass'),
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

        // Get DL user
        $downloaduser = $this->downloadUser;

        $relevantRoute = URL::route('store.centres.registrations.summary');
        $specificRoute = URL::route('store.centre.registrations.summary', ['centre' => $this->centre->id ]);
        $mvlRoute = URL::route('store.vouchers.mvl.export');

        $this->actingAs($ccuser, 'store')
            ->visit(URL::route('store.dashboard'))
            ->dontSee($relevantRoute)
            ->dontSee($mvlRoute)
            ->dontSee($specificRoute)
            ->see("print-registrations")
        ;

        Auth::logout();

        $this->actingAs($downloaduser, 'store')
            ->visit(URL::route('store.dashboard'))
            ->dontSee($relevantRoute)
            ->dontSee($mvlRoute)
            ->see($specificRoute)
            ->see("print-registrations")
        ;

        Auth::logout();

        $this->actingAs($fmuser, 'store')
            ->visit(URL::route('store.dashboard'))
            ->see($relevantRoute)
            ->see($mvlRoute)
            ->dontSee($specificRoute)
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
    public function itShowsTheExportButtonWithReleventTextForRole()
    {
        // Get DL user
        $downloaduser = $this->downloadUser;

        $this->actingAs($downloaduser, 'store')
            ->visit(URL::route('store.dashboard'))
            ->see("export-centre-registrations")
            ->seeInElement('li', $this->centre->name);
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
            ->see(URL::route('store.centre.registrations.collection', ['centre' => $this->centre->id ]))
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
