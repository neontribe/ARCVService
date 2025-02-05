<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\AdminUser;
use App\Market;
use App\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $market;
    protected $admin_user;


    protected function setUp(): void
    {
        parent::setUp();
        $this->admin_user = factory(AdminUser::class)->create();
        $this->market = factory(Market::class)->create();
    }

    public function testStoreBatchWithoutStartEndSponsor()
    {
        $this->actingAs($this->admin_user, 'admin')
            ->post(route('admin.vouchers.storebatch'), [
                'sponsor_id' => '',
                'start' => '',
                'end' => '',
            ])
            ->assertStatus(302)
            ->assertSessionMissing('notification')
            ->assertSessionHasErrors([
                'start' => 'The start field is required.',
                'end' => 'The end field is required.',
                'sponsor_id' => 'The sponsor id field is required.',
            ]);
    }

    public function testStoreBatchStartEndSwapped()
    {
        $this->actingAs($this->admin_user, 'admin')
            ->post(route('admin.vouchers.storebatch'), [
                'sponsor_id' => $this->market->sponsor_id,
                'start' => '10',
                'end' => '1',
            ])
            ->assertStatus(302)
            ->assertSessionMissing('notification')
            ->assertSessionHasErrors([
                'end-serial' => 'The end must be greater than or equal to start.',
            ]);
    }

    public function testStoreBatchInvalidSponsor()
    {
        $this->actingAs($this->admin_user, 'admin')
            ->post(route('admin.vouchers.storebatch'), [
                'sponsor_id' => $this->market->sponsor_id + 1,
            ])
            ->assertStatus(302)
            ->assertSessionMissing('notification')
            ->assertSessionHasErrors([
                'sponsor_id' => 'The selected sponsor id is invalid.',
            ]);
    }

    public function testStoreBatchSuccessMsg()
    {
        $shortcode = $this->market->sponsor_shortcode;
        $start = '1';
        $end = '10';
        $notification_msg = trans('service.messages.vouchers_create.success', [
            'shortcode' => $shortcode,
            'start' => $start,
            'end' => $end,
        ]);
        $this->actingAs($this->admin_user, 'admin')
            ->post(route('admin.vouchers.storebatch'), [
                'sponsor_id' => $this->market->sponsor_id,
                'start' => $start,
                'end' => $end,
            ])
            ->assertStatus(302)
            ->assertSessionHas('notification', $notification_msg);
    }

    public function testStoreBatch()
    {
        $shortcode = $this->market->sponsor_shortcode;
        $this->actingAs($this->admin_user, 'admin')
            ->post(route('admin.vouchers.storebatch'), [
                'sponsor_id' => $this->market->sponsor_id,
                'start' => '50',
                'end' => '59',
            ])
            ->assertStatus(302);

        $vouchers = Voucher::all();

        // Assert that eleven vouchers have been made and are in the expected state and land within the expected range.
        $this->assertCount(10, $vouchers);
        foreach ($vouchers as $voucher) {
            $this->assertEquals('printed', $voucher->currentstate);

            // Assert that the voucher code starts with the Sponsor shortcode.
            $this->assertStringStartsWith($shortcode, $voucher->code);

            // Assert that the voucher code lands between 50 and 60 (our generated range).
            $this->assertMatchesRegularExpression('/,*5[0-9]/', $voucher->code);
        }
    }

    public function testItCanStoreZeroPaddedVouchersCorrectly()
    {
        $shortcode = $this->market->sponsor_shortcode;
        $this->actingAs($this->admin_user, 'admin')
            ->post(route('admin.vouchers.storebatch'), [
                'sponsor_id' => $this->market->sponsor_id,
                'start' => '9999',
                'end' => '10001',
            ])
            ->assertStatus(302);

        $vouchers = Voucher::all();

        // Assert that 3 vouchers have been made and are in the expected state and land within the expected range.
        $this->assertCount(3, $vouchers);
        foreach ($vouchers as $voucher) {
            $this->assertEquals('printed', $voucher->currentstate);

            // Assert that the voucher code starts with the Sponsor shortcode.
            $this->assertStringStartsWith($shortcode, $voucher->code);

            // the voucher code must be padded to 5
            $this->assertMatchesRegularExpression('/' . $shortcode . '(09999|10000|10001)$/', $voucher->code);
        }
    }
}
