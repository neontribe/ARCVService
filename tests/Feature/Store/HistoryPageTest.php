<?php

namespace Tests;

use App\Centre;
use App\CentreUser;
use App\Registration;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class HistoryPageTest extends StoreTestCase
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

    /** @test **/
    public function itShowsAlertWhenRegistrationHasNoBundlesAssigned()
    {
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.collection-history', [ 'id' => $this->registration ]))
            ->see('This family has not collected.')
        ;
    }
}
