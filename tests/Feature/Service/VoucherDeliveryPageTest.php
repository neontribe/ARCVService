<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use App\Centre;
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
        $this->centres = factory(Centre::class, 5)->create();
        $this->voucherDeliveryRoute = route('admin.deliveries.create');
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
            ->seeElement('select[name=centre]')
            ->seeInElement('label[for=centre]', 'Centre')
            ->seeElement('input[type=text]')
            ->seeInElement('label[for=voucher-start]', 'Start Voucher')
            ->seeElement('input[type=text]')
            ->seeInElement('label[for=voucher-end]', 'End Voucher')
            ->seeElement('input[type=date]')
            ->seeInElement('label[for=date-sent]', 'Date Sent')
            ->seeInElement('button[type=submit]', 'Create Delivery')
            ->seeInElementAtPos('select[name=centre] option', 'Choose one', 0)
            ->seeInElementAtPos('select[name=centre] option', $this->centres[0]['name'], 1)
            ->seeInElementAtPos('select[name=centre] option', $this->centres[1]['name'], 2)
            ->seeInElementAtPos('select[name=centre] option', $this->centres[2]['name'], 3)
            ->seeInElementAtPos('select[name=centre] option', $this->centres[3]['name'], 4)
            ->seeInElementAtPos('select[name=centre] option', $this->centres[4]['name'], 5);
    }
}
