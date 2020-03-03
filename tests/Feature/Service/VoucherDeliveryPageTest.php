<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Centre;
use App\Sponsor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class VoucherDeliveryPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var Centre $centre */
    private $centre;

    private $voucherDeliveryRoute;

    public function setUp()
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->voucherDeliveryRoute = route('admin.deliveries.create');

        $sponsor = factory(Sponsor::class)->create();

        // Create 3 centres in an area
        $this->centre = factory(Centre::class, 3)->create()->each(
            function ($c) use ($sponsor) {
                $c->sponsor_id = $sponsor->id;
                $c->save();
            }
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function testItShowsAFormWithInputs()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->visit($this->voucherDeliveryRoute)
            ->assertResponseOk()
            ->seeInElement('h1', 'Send vouchers')
            ->seeInElement('p', 'Use the form below to mark a batch of vouchers as being sent. Add the centre they\'re being sent to, start and end voucher codes of the batch and the date they\'re being sent.')
            ->seeElement('form')
            ->seeElement('select[name="centre"]')
            ->seeInElement('label[for="centre"]', 'Centre')
            ->seeElement('input[type="text"]')
            ->seeInElement('label[for="voucher-start"]', 'Start Voucher')
            ->seeElement('input[type="text"]')
            ->seeInElement('label[for="voucher-end"]', 'End Voucher')
            ->seeElement('input[type="date"]')
            ->seeInElement('label[for="date-sent"]', 'Date Sent')
            ->seeInElement('button[type=submit]', 'Create Delivery')
            ->seeInElementAtPos('select[name="centre"]', 'Choose one', 0)
        ;
    }
}
