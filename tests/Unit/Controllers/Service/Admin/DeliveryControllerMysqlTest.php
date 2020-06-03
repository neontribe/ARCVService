<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\Centre;
use App\Sponsor;
use App\User;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Config;
use Exception;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\StoreTestCase;

class DeliveryControllerMysqlTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $centre;
    protected $user;
    protected $sponsor;
    protected $rangeCodes;

    // TODO : Consider pulling this out to a config option or environment variable
    private const TESTING_MYSQL_FALLBACK = 'testing-mysql';

    protected function setUp(): void
    {
        parent::setUp();

        // Fallback to the MySQL testing database if the default testing database doesn't use the MySQL driver
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver !== 'mysql') {
            $connection = self::TESTING_MYSQL_FALLBACK;
            Config::set('database.default', $connection);
        }

        // Check we can connect to the database before we test. This test is effectively optional, so it would be rude
        // to just error out
        try {
            $this->runDatabaseMigrations();
        } catch (Exception $exception) {
            $this->markTestSkipped(
                'Raw queries with specific functions need the MySQL database "' . $connection .
                '", but it was unavailable: ' . $exception->getMessage()
            );
        }

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

        Auth::login($this->user);

        foreach ($this->rangeCodes as $rangeCode) {
            $voucher = factory(Voucher::class, 'printed')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
        }

        Auth::logout();
    }

    /** @test */
    public function testItCanMakeADelivery()
    {
        // The post data
        $now = Carbon::today()->format('Y-m-d');

        $data = [
            'centre' => $this->centre->id,
            'voucher-start' => 'TST0102',
            'voucher-end' => 'TST0104',
            'date-sent' => $now,
        ];

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
            ->post($requestRoute, $data)
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

        $vouchers->each(function ($v) use ($now) {
            $this->assertNotNull($v->delivery);
            $this->assertEquals($this->centre->id, $v->delivery->centre->id);
            $this->assertEquals($now, $v->delivery->dispatched_at->format('Y-m-d'));
        });
    }
}
