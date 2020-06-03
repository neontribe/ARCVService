<?php

namespace Tests\Feature\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeliveriesControllerTest extends TestCase
{
    use DatabaseMigrations;

    /** @var AdminUser $adminUser */
    private $adminUser;

    /** @var Centre $centre */
    private $centre;

    private $vouchersDeliveryroute;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->centre = factory(Centre::class)->create();
        $this->vouchersDeliveryroute = route('admin.deliveries.store');
    }

    /**
     * @test
     *
     * @return void
     */
    public function testStoreWithoutStartEndDateErrors()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => '',
                'voucher-start' => '',
                'voucher-end' => '',
                'date-sent' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertStatus(302)
            ->assertSessionMissing('message')
            ->assertSessionHasErrors([
                'centre' => 'The centre field is required.',
                'voucher-start' => 'The voucher-start field is required.',
                'voucher-end' => 'The voucher-end field is required.'
            ]);
    }

    /**
     * @test
     *
     * @return void
     */
    public function testStoreStartEndSwapped()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => $this->centre->id,
                'voucher-start' => '10',
                'voucher-end' => '1',
                'date-sent' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertStatus(302)
            ->assertSessionMissing('message')
            ->assertSessionHasErrors([
                'voucher-end' => 'The voucher-end field must be greater than the voucher-start field.'
            ]);
    }

    /**
     * @test
     *
     * @return void
     */
    public function testStoreCentreIsNotNumberErrors()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => 'not a number but a wombat',
            ])
            ->assertStatus(302)
            ->assertSessionMissing('message')
            ->assertSessionHasErrors([
                'centre' => 'The centre must be a number.'
            ]);
        ;
    }

    /**
     * @test
     *
     * @return void
     */
    public function testStoreStartIsNotTheSameSponsorAsEndErrors()
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => $this->centre->id,
                'voucher-start' => 'EMRTP0007',
                'voucher-end' => 'KNTLN0009',
                'date-sent' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertStatus(302)
            ->assertSessionMissing('message')
            ->assertSessionHasErrors([
                'voucher-end' => 'The voucher-end field must be the same sponsor as the voucher-start field.'
            ]);
    }
}
