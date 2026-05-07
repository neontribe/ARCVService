<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveriesControllerTest extends TestCase
{
    use RefreshDatabase;

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
     *
     * @return void
     */
    public function testStoreWithoutStartEndDateErrors(): void
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
                'voucher-end' => 'The voucher-end field is required.',
            ]);
    }

    /**
     *
     * @return void
     */
    public function testStoreStartEndSwapped(): void
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
                'voucher-end' => 'The voucher-end field must be greater than the voucher-start field.',
            ]);
    }

    /**
     *
     * @return void
     */
    public function testStoreCentreIsNotNumberErrors(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => 'not a number but a wombat',
            ])
            ->assertStatus(302)
            ->assertSessionMissing('message')
            ->assertSessionHasErrors([
                'centre' => 'The centre must be a number.',
            ]);
    }

    /**
     *
     * @return void
     */
    public function testStoreStartIsNotTheSameSponsorAsEndErrors(): void
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
                'voucher-end' => 'The voucher-end field must be the same sponsor as the voucher-start field.',
            ]);
    }

    // =========================================================================
    // NEW TESTS
    // =========================================================================

    // =========================================================================
    // index / create — smoke tests
    // =========================================================================

    /**
     * The index page lists deliveries and must return 200 for an authenticated
     * admin regardless of whether any deliveries exist.
     */
    public function testIndexReturns200(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.deliveries.index'))
            ->assertStatus(200);
    }

    /**
     * The create form must render for an authenticated admin without error.
     */
    public function testCreateReturns200(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->get(route('admin.deliveries.create'))
            ->assertStatus(200);
    }

    // =========================================================================
    // store — date-sent validation
    // =========================================================================

    /**
     * Omitting date-sent must trigger a validation error rather than reaching
     * Carbon::createFromFormat() with a null value, which would throw or return
     * false depending on the Carbon version.
     *
     * If this test fails it means date-sent is not yet in the AdminNewDeliveryRequest
     * rules and a 'required|date_format:Y-m-d' rule should be added.
     */
    public function testStoreMissingDateSentReturnsValidationError(): void
    {
        $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => $this->centre->id,
                'voucher-start' => 'DLVT0001',
                'voucher-end' => 'DLVT0003',
                'date-sent' => '',
            ])
            ->assertStatus(302)
            ->assertSessionMissing('message')
            ->assertSessionHasErrors(['date-sent']);
    }
}
