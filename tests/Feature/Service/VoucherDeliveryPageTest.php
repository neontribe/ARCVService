<?php

namespace Tests\Feature\Service;

use App\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class VoucherDeliveryPageTest extends StoreTestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    private $voucherDeliveryRoute;

    public function setUp()
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
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
