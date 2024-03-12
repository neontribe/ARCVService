<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Centre;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeliveriesControllerTest extends TestCase
{
    use DatabaseMigrations;

    private AdminUser $adminUser;
    private Centre $centre;
    private string $vouchersDeliveryroute;
    private Collection $vouchers;

    public function setUp(): void
    {
        parent::setUp();

        $this->adminUser = factory(AdminUser::class)->create();
        $this->centre = factory(Centre::class)->create();
        $this->vouchers = factory(Voucher::class, 5)->state('printed')->create();
        $this->vouchersDeliveryroute = route('admin.deliveries.store');

        // TODO The vouchers created have random code lengths,is that right?
        $shortcode = $this->vouchers[0]->sponsor->shortcode;
        foreach ($this->vouchers as $index => $voucher) {
            $voucher->code = $shortcode . sprintf("%04d", $index);
            $voucher->save();
        }
    }

    /**
     * @test
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
                'voucher-end' => 'The voucher-end field is required.'
            ]);
    }

    /**
     * @test
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
                'voucher-end' => 'The voucher-end field must be greater than the voucher-start field.'
            ]);
    }

    /**
     * @test
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
                'centre' => 'The centre must be a number.'
            ]);
        ;
    }

    /**
     * @test
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
                'voucher-end' => 'The voucher-end field must be the same sponsor as the voucher-start field.'
            ]);
    }

    /**
     * @test
     */
    public function testStore(): void
    {
        $vouchers = Voucher::all()->sortBy("code");
        $firstVoucher = $vouchers->first();
        $lastVoucher = $vouchers->last();
        $response = $this->actingAs($this->adminUser, 'admin')
            ->post($this->vouchersDeliveryroute, [
                'centre' => $this->centre->id,
                'voucher-start' => $firstVoucher->code,
                'voucher-end' => $lastVoucher->code,
                'date-sent' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertStatus(302)
            ->assertRedirectToRoute("admin.deliveries.create");

        // TODO This needs loads more work, I can't get a deliverable range so the test never goes beyond here
        // https://github.com/neontribe/ARCVService/blob/096bd1850c8a1a4baaf5e95d916a6ef6a062012f/app/Http/Controllers/Service/Admin/DeliveriesController.php#L63

    }
}
