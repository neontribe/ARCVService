<?php
namespace Tests\Feature\Store;

use App\Carer;
use App\Centre;
use App\CentreUser;
use App\Child;
use App\Family;
use App\Registration;
use Carbon\Carbon;
use Exception;
use Tests\CreatesApplication;
use Tests\MysqlStoreTestCase;
use URL;

class SearchPageFuzzyTest extends MysqlStoreTestCase
{
    use CreatesApplication;

    // A centre and user to make registrations for and search with
    private $centre;
    private $centreUser;

    public function setUp(): void
    {
        parent::setUp();

        // Create a centre and user that we can act as
        $this->centre = factory(Centre::class)->create();
        $this->centreUser =  factory(CentreUser::class)->create([
            "name"  => "test user",
            "email" => "testuser@example.com",
            "password" => bcrypt('test_user_pass'),

        ]);
        $this->centreUser->centres()->attach($this->centre->id, ['homeCentre' => true]);
    }

    public function itCanSearchForExactMatches()
    {
        $this->markTestIncomplete("This test has not been implemented but likely can be");
    }

    public function itCanSearchForStartOfStringMatches()
    {
        $this->markTestIncomplete("This test has not been implemented yet but likely can be");
    }

    /** @test */
    public function itCanSearchForAcronymMatches()
    {
        // https://github.com/TomLingham/Laravel-Searchy/tree/2.0#acronymmatcher

        // Make some named registrations
        $this->createRegWithCarer('Bruce Campbell');
        $this->createRegWithCarer('Sam Raimi');
        $this->createRegWithCarer('Ted Raimi');
        $this->createRegWithCarer('Ivan Raimi');

        // Visit the registration index and search for initials "tr"
        $this->actingAs($this->centreUser, 'store')
            ->visit(URL::route('store.registration.index'))
            ->type('tr', 'family_name')
            ->press('search');

        // Only see Ted Raimi, whose initials match
        $this->see("Ted Raimi");
        $this->dontSee("Bruce Campbell");
        $this->dontSee("Sam Raimi");
        $this->dontSee("Ivan Raimi");
    }

    public function itCanSearchForConsecutiveMatches()
    {
        $this->markTestIncomplete("This test has not been implemented yet but likely can be");
    }

    public function itCanSearchForStarOfWordMarches()
    {
        $this->markTestIncomplete("This test has not been implemented yet but likely can be");
    }

    public function itCanSearchForStudlyCaseMatches()
    {
        $this->markTestIncomplete("This test has not been implemented yet but likely can be");
    }

    public function itCanSearchForSubstringMatches()
    {
        $this->markTestIncomplete("This test has not been implemented yet but likely can be");
    }

    /**
     * Create a registration in our centre with a primary carer of given name.
     *
     * TODO : As soon as this becomes awkward, consider pulling it our database testing factory methods
     *
     * @param string $name the primary carer's name
     * @return Registration the created and saved model
     * @throws Exception if saving went wrong
     */
    private function createRegWithCarer(string $name) {
        // Make and save a family in our centre
        $family = factory(Family::class)->make();
        $family->lockToCentre($this->centre);
        $family->save();

        // Add the carer, of our name, and a few children
        $family->carers()->save(new Carer(array("name" => $name)));
        $family->children()->saveMany(factory(Child::class, random_int(0, 4))->make());

        // Make and save a registration and attach it to our family and centre
        $registration = new Registration([
            'eligibility' => 'healthy-start',
            'consented_on' => Carbon::now(),
        ]);
        $registration->family()->associate($family);
        $registration->centre()->associate($this->centre);
        $registration->save();

        return $registration;
    }

}
