<?php

namespace Tests\Unit\Controllers\Api;

use App\AdminUser;
use App\Market;
use App\Voucher;
use App\Trader;
use App\User;
use App\Http\Controllers\API\TraderController;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class VoucherControllerTest extends TestCase
{
    use DatabaseMigrations;

    protected $market;
    protected $admin_user;


    protected function setUp()
    {
        parent::setUp();
        $this->admin_user = factory(AdminUser::class)->create();
        $this->market = factory(Market::class)->create();
    }

    public function testStoreBatchWithoutStartEndSponsor() {
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
            ])
        ;
    }

    public function testStoreBatch() {
        $shortcode = $this->market->sponsor_shortcode;

        $start = '1';
        $end = '10';
        $notification_msg = trans('service.messages.vouchers_create_success',[
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
            ->assertSessionHas('notification', $notification_msg)
        ;

        $vouchers = Voucher::findMany(range(0, 10));
        // Asset that ten vouchers have been made.
        $this->assertCount(10, $vouchers);
    }
}
