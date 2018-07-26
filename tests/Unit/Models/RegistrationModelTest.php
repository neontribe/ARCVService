<?php

namespace Tests;

use App\Centre;
use App\Family;
use App\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RegistrationModelTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function itCanBeCreated()
    {
        $family = factory(Family::class)->create();
        $centre = factory(Centre::class)->create();

        $registration = new Registration;
        $registration->centre_id = $centre->id;
        $registration->eligibility = "other";
        $registration->family_id = $family->id;

        $this->assertTrue($registration->save());
    }

    /** @test */
    public function itCanGetReminders()
    {
        // Setup a registration with random family and centre
        $registration = factory(Registration::class)->make();

        $reminders = $registration->getStatus();
        $this->assertContains(Registration::REMINDER_TYPES['FoodDiaryNeeded'], $reminders);
        $this->assertContains(Registration::REMINDER_TYPES['FoodChartNeeded'], $reminders);

        $registration->fm_diary_on = Carbon::now()->startOfMonth();
        $reminders = $registration->getStatus();
        $this->assertNotContains(Registration::REMINDER_TYPES['FoodDiaryNeeded'], $reminders);
        $this->assertContains(Registration::REMINDER_TYPES['FoodChartNeeded'], $reminders);

        $registration->fm_chart_on = Carbon::now()->startOfMonth();
        $reminders = $registration->getStatus();
        $this->assertNotContains(Registration::REMINDER_TYPES['FoodDiaryNeeded'], $reminders);
        $this->assertNotContains(Registration::REMINDER_TYPES['FoodChartNeeded'], $reminders);
    }

    /** @test */
    public function itCanReturnRegistrationsOnlyForActiveFamilies()
    {
        // Create a centre
        $centre = factory(Centre::class)->create();

        // Create 4 random registrations (and families etc.) in that centre.
        $registrations = factory(Registration::class, 4)->create([
            'centre_id' => $centre->id,
        ]);

        // Check that we have 4.
        $this->assertEquals(Registration::whereActiveFamily()->count(), 4);

        // A family has left.
        $family = $registrations->first()->family;
        $family->leaving_on = Carbon::now();
        $family->save();

        // check there are only 3.
        $this->assertEquals(Registration::whereActiveFamily()->count(), 3);
    }
}
