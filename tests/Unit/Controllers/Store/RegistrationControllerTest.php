<?php

namespace Tests;

use App\Centre;
use App\CentreUser;
use App\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
USE URL;

class RegistrationControllerTest extends StoreTestCase
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
        $this->centreUser = factory(CentreUser::class)->create([
            "name" => "test user",
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
        $this->assertEquals('The given data was invalid.', $response->exception->getMessage());
    }

    /** @test */
    public function itWillRejectUpdatesIfFamilyHasLeft()
    {
        $family = $this->registration->family;
        $family->leaving_on = Carbon::now();
        $family->leaving_reason = config('arc.leaving_reasons')[0];
        $family->save();

        $data = [
            'pri_carer' => ['A String'],
            'children' => [
                0 => ['dob' => '2017-09']
            ]
        ];

        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.edit', $this->registration->id))
            ->call(
                'PUT',
                route('store.registration.update', $this->registration->id),
                $data
            )
        ;
        $this->assertResponseStatus(403);
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
}