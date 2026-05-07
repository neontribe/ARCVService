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

    // =========================================================================
    // Existing tests
    // =========================================================================

    public function testItCanMakeADelivery(): void
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

    public function testItCannotMakeADeliveryBecauseAVoucherIsDelivered(): void
    {
        // Record a voucher on a delivery
        $v = Voucher::findByCode("TST0103");
        $d = new Delivery([
            'centre_id' => $this->centre->id,
            'range' => 'TST0103-TST0103',
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

    public function testItCannotMakeADeliveryBecauseAVoucherIsNotPrinted(): void
    {
        // Record a voucher that is recorded
        $v = Voucher::findByCode("TST0103");
        $v->currentstate = "recorded";
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

    // =========================================================================
    // index — smoke test
    // =========================================================================

    /**
     * The index page must render for an authenticated admin regardless of
     * whether any deliveries exist.
     */
    public function testIndexReturns200(): void
    {
        $this->actingAs($this->user, 'admin')
            ->visit(route('admin.deliveries.index'))
            ->assertResponseStatus(200);
    }

    // =========================================================================
    // store — validation failures
    // =========================================================================

    public function testStoreWithoutRequiredFieldsErrors(): void
    {
        $formRoute = route('admin.deliveries.create');
        $requestRoute = route('admin.deliveries.store');

        $this->actingAs($this->user, 'admin')
            ->post($requestRoute, [
                'centre' => '',
                'voucher-start' => '',
                'voucher-end' => '',
                'date-sent' => $this->now,
            ])
            ->assertResponseStatus(302);

        $this->assertSessionMissing('message');
        $this->assertSessionHasErrors([
            'centre' => 'The centre field is required.',
            'voucher-start' => 'The voucher-start field is required.',
            'voucher-end' => 'The voucher-end field is required.',
        ]);
    }

    public function testStoreStartEndSwappedErrors(): void
    {
        $requestRoute = route('admin.deliveries.store');

        $this->actingAs($this->user, 'admin')
            ->post($requestRoute, [
                'centre' => $this->centre->id,
                'voucher-start' => 'TST0104',
                'voucher-end' => 'TST0102',
                'date-sent' => $this->now,
            ])
            ->assertResponseStatus(302);

        $this->assertSessionMissing('message');
        $this->assertSessionHasErrors([
            'voucher-end' => 'The voucher-end field must be greater than the voucher-start field.',
        ]);
    }

    public function testStoreCentreIsNotNumberErrors(): void
    {
        $requestRoute = route('admin.deliveries.store');

        $this->actingAs($this->user, 'admin')
            ->post($requestRoute, [
                'centre' => 'not a number but a wombat',
            ])
            ->assertResponseStatus(302);

        $this->assertSessionMissing('message');
        $this->assertSessionHasErrors([
            'centre' => 'The centre must be a number.',
        ]);
    }

    public function testStoreStartIsNotTheSameSponsorAsEndErrors(): void
    {
        $requestRoute = route('admin.deliveries.store');

        // EMRTP and KNTLN are different shortcodes — the form request rejects
        // mixed-sponsor ranges before any database query is issued.
        $this->actingAs($this->user, 'admin')
            ->post($requestRoute, [
                'centre' => $this->centre->id,
                'voucher-start' => 'EMRTP0007',
                'voucher-end' => 'KNTLN0009',
                'date-sent' => $this->now,
            ])
            ->assertResponseStatus(302);

        $this->assertSessionMissing('message');
        $this->assertSessionHasErrors([
            'voucher-end' => 'The voucher-end field must be the same sponsor as the voucher-start field.',
        ]);
    }

    /**
     * Omitting date-sent must trigger a validation error rather than reaching
     * Carbon::createFromFormat() with a null value.
     *
     * If this test fails, date-sent is not yet in AdminNewDeliveryRequest rules
     * and a 'required|date_format:Y-m-d' rule should be added.
     */
    public function testStoreMissingDateSentReturnsValidationError(): void
    {
        $requestRoute = route('admin.deliveries.store');

        $this->actingAs($this->user, 'admin')
            ->post($requestRoute, [
                'centre' => $this->centre->id,
                'voucher-start' => 'TST0102',
                'voucher-end' => 'TST0104',
                'date-sent' => '',
            ])
            ->assertResponseStatus(302);

        $this->assertSessionMissing('message');
        $this->assertSessionHasErrors(['date-sent']);
    }
}
