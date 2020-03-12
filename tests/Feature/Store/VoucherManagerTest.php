<?php
namespace Tests;

use App\Centre;
use App\CentreUser;
use App\Child;
use App\Family;
use App\Registration;
use App\Sponsor;
use App\Voucher;
use Auth;
use Config;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;

class VoucherManagerTest extends StoreTestCase
{
    use DatabaseMigrations;

    /**
     *
     * @var Centre $centre
     * @var CentreUser $centreUser
     * @var CentreUser $fmUser
     * @var Registration $registration
     */
    private $centre;

    private $centreUser;

    private $fmUser;

    private $testCodes;

    private $registration;

    public function setUp()
    {
        parent::setUp();

        $this->centre = factory(Centre::class)->create();

        // Food matters user
        $this->fmUser = factory(CentreUser::class, 'FMUser')->create([
            "name" => "FM test user",
            "email" => "testfmuser@example.com",
            "password" => bcrypt('test_fmuser_pass'),
        ]);
        $this->fmUser->centres()->attach($this->centre->id, [
            'homeCentre' => true
        ]);

        // Make the centre a registration
        $this->registration = factory(Registration::class)->create([
            "centre_id" => $this->centre->id
        ]);

        // Make some vouchers
        $this->testCodes = [
            'TST09999',
            'TST10000',
            'TST10001'
        ];

        Auth::login($this->fmUser);

        foreach ($this->testCodes as $testCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $testCode
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            $voucher->applyTransition('dispatch');
        }

        Auth::logout();
    }

    /** @test */
    public function testThreeColumnsAreVisible()
    {
        // Check we can see the this family div
        // Check we can see the Collection History div
        // Check we can see the Allocate Vouchers div
        $this->actingAs($this->fmUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->seeElement("#this-family")
            ->seeElement("#collection-history")
            ->seeElement("#allocate-vouchers");
    }

    /** @test */
    public function itCanShowFamilyWarnings()
    {
        /**
         * Create
         * - a registration
         * - on a center
         * - in an area that checks for ID
         * - for a family
         * - with kids who havn't been checked for ID.
         */

        // Make a sponsor
        $sponsor = factory(Sponsor::class)->create();

        // Set the verification and extended rules to match it.
        Config::set('arc.verifies_children', [$sponsor->shortcode]);
        Config::set('arc.extended_sponsors', [$sponsor->shortcode]);

        // Make a Centre in it.
        $centre =  factory(Centre::class)->create([
            'sponsor_id' => $sponsor->id,
        ]);

        // Make a family, with unverified kids.
        $family = factory(Family::class)->create();
        $unverifiedKids = factory(Child::class, 3)->states('unverified')->make();
        $family->children()->saveMany($unverifiedKids);

        // Link it all up to a registration.
        $registration = factory(Registration::class)->create([
            'centre_id' => $centre->id,
            'family_id' => $family->id,
        ]);

        // Make a CentreUser
        $ccUser = factory(CentreUser::class)->create([
            "name" => "CC test user",
            "email" => "testccuser@example.com",
            "password" => bcrypt('test_ccuser_pass'),
        ]);
        $ccUser->centres()->attach($centre->id, [
            'homeCentre' => true
        ]);

        // Navigate to the page as that user.
        $this->actingAs($ccUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $registration ]))
        ;

        // We can see the warnings area
        $this->seeElement("#family-warning");

        // Fetch the registration in question and check it's notices are present.
        $registration->fresh();
        $notices = $registration->getValuation()->getNoticeReasons();
        $this->assertGreaterThan(0, count($notices));

        // We can see the notice reason
        foreach ($notices as $notice) {
            $this->see($notice["reason"]);
        }
    }

    /** @test */
    public function testFollowLinks()
    {

        // Check edit family link
        $this->actingAs($this->fmUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->click('edit-family-link')
            ->assertResponseOk();

        // Check find another family link
        $this->actingAs($this->fmUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->click('edit-family-link')
            ->assertResponseOk();

        // check full collection link
        $this->actingAs($this->fmUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->click('full-collection-link')
            ->assertResponseOk();
    }

    /** @test */
    public function testAddBulkVoucher()
    {
        // check we can bulk add vouchers
        $this->actingAs($this->fmUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->type('tst09999', 'start')
            ->type('tst10001', 'end')
            ->press('range-add')
            ->seePageIs(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->see('TST09999')
            ->see('TST10000')
            ->see('TST10001')
        ;
        // Three vouchers and the delete all button.
        $this->assertEquals(4, count($this->crawler->filter('.delete-button')));

        // check we can remove a bundle of vouchers
        $this->press('delete-all-button');
        // The delete all button is hidden
        $this->assertEquals(1, count($this->crawler->filter('.delete-button')));
    }

    /** @test */
    public function testAddSingleVoucher()
    {
        // check we can add a single voucher
        $this->actingAs($this->fmUser, 'store')
            ->visit(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->type('tst09999', 'start')
            ->press('add-button')
            ->seePageIs(URL::route('store.registration.voucher-manager', [ 'id' => $this->registration ]))
            ->see('TST09999')
        ;
        // One voucher and the delete all button.
        $this->assertEquals(2, count($this->crawler->filter('.delete-button')));

        // check we can remove a voucher
        $this->press('delete-button');
        // The delete all button is hidden
        $this->assertEquals(1, count($this->crawler->filter('.delete-button')));
    }
}
