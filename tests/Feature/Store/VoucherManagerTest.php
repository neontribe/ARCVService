<?php
namespace Tests;

use App\Centre;
use App\CentreUser;
use App\Registration;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use URL;
use Tests\StoreTestCase;

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

    /**
     * *
     *
     * @test
     */
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