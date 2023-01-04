<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\Centre;
use App\Delivery;
use App\Sponsor;
use App\User;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Tests\MysqlStoreTestCase;

class DeliveryControllerMysqlTest extends MysqlStoreTestCase
{
    protected $centre;
    protected $user;
    protected $sponsor;
    protected $rangeCodes;
    protected $requestData;
    protected $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rangeCodes = [
            'TST0101',
            'TST0102',
            'TST0103',
            'TST0104',
            'TST0105',

            'TST0201',
            'TST0202',
            'TST0203',
            'TST0204',
            'TST0205',

            'TST0301',
            'TST0302',
            'TST0303',
            'TST0304',
            'TST0305',
        ];

        // Make a sponsor to match
        $this->sponsor = factory(Sponsor::class)->create(
            ['shortcode' => 'TST']
        );

        // Make a centre to send things to
        $this->centre = factory(Centre::class)->create([
            'sponsor_id' => $this->sponsor->id,
        ]);

        $this->user = factory(User::class)->create();

        $this->now = Carbon::today()->format('Y-m-d');
        $this->requestData["TST0102-TST0104"] = [
            'centre' => $this->centre->id,
            'voucher-start' => 'TST0102',
            'voucher-end' => 'TST0104',
            'date-sent' => $this->now,
        ];

        Auth::login($this->user);

        foreach ($this->rangeCodes as $rangeCode) {
            factory(Voucher::class)->state('printed')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
        }

        Auth::logout();
    }

    /** @test */
    public function testItCanMakeADelivery()
    {

        // Set some routes
        $formRoute = route('admin.deliveries.create');
        $requestRoute = route('admin.deliveries.store');
        $successRoute = route('admin.deliveries.index');

        // Set the message to look for
        $msg = trans('service.messages.vouchers_delivery.success', [
            'centre_name' => $this->centre->name,
        ]);

        // Make the patch
        $this->actingAs($this->user, 'admin')
            ->visit($formRoute)
            ->post($requestRoute, $this->requestData["TST0102-TST0104"])
            ->followRedirects()
            ->seePageIs($successRoute)
            ->see($msg)
        ;

        // fetch those back.
        $vouchers = Voucher::where('currentstate', 'dispatched')
            ->with('delivery')
            ->get();

        // Check there are 3.
        $this->assertCount(3, $vouchers);

        $vouchers->each(function ($v) {
            $this->assertNotNull($v->delivery);
            $this->assertEquals($this->centre->id, $v->delivery->centre->id);
            $this->assertEquals($this->now, $v->delivery->dispatched_at->format('Y-m-d'));
        });
    }

    /** @test */
    public function testItCannotMakeADeliveryBecauseAVoucherIsDelivered()
    {
        // Record a voucher on a delivery
        $v = Voucher::findByCode("TST0103");
        $d = new Delivery([
            'centre_id' => $this->centre->id,
            'range' =>'TST0103-TST0103',
            'dispatched_at' => $this->now,
        ]);
        $d->save();
        $d->vouchers()->save($v);


        // Set some routes
        $formRoute = route('admin.deliveries.create');
        $requestRoute = route('admin.deliveries.store');

        // Set the message to look for
        $msg = trans('service.messages.vouchers_delivery.blocked');

        // Make the patch
        $this->actingAs($this->user, 'admin')
            ->visit($formRoute)
            ->post($requestRoute, $this->requestData["TST0102-TST0104"])
            ->followRedirects()
            ->seePageIs($formRoute)
            ->see($msg)
        ;
    }

    /** @test */
    public function testItCannotMakeADeliveryBecauseAVoucherIsNotPrinted()
    {
        // Record a voucher that is recorded
        $v = Voucher::findByCode("TST0103");
        $v->currentstate="recorded";
        $v->save();

        // Set some routes
        $formRoute = route('admin.deliveries.create');
        $requestRoute = route('admin.deliveries.store');

        // Set the message to look for
        $msg = trans('service.messages.vouchers_delivery.blocked');

        // Make the patch
        $this->actingAs($this->user, 'admin')
            ->visit($formRoute)
            ->post($requestRoute, $this->requestData["TST0102-TST0104"])
            ->followRedirects()
            ->seePageIs($formRoute)
            ->see($msg)
        ;
    }
}
