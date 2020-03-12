<?php

namespace Tests\Unit\Controllers\Service\Admin;

use App\Sponsor;
use App\User;
use App\Voucher;
use Auth;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Config;
use Exception;
use Tests\StoreTestCase;

class VoucherControllerMysqlTest extends StoreTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $sponsor;
    protected $rangeCodes;

    // TODO : Consider pulling this out to a config option or environment variable
    private const TESTING_MYSQL_FALLBACK = 'testing-mysql';

    protected function setUp()
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

        $this->user = factory(User::class)->create();

        Auth::login($this->user);

        foreach ($this->rangeCodes as $rangeCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            $voucher->applyTransition('dispatch');
        }

        Auth::logout();
    }

    /** @test */
    public function testItCanVoidVoucherCodes()
    {
        // The post data
        $data = [
            'voucher-start' => 'TST0102',
            'voucher-end' => 'TST0104',
            'transition' => 'void'
        ];

        // Set some routes
        $formRoute = route('admin.vouchers.void');
        $requestRoute = route('admin.vouchers.updatebatch');
        $successRoute = route('admin.vouchers.index');

        // Set the message to look for
        $msg = trans('service.messages.vouchers_batchtransition.success', [
            'transition_to' => 'retired',
            'shortcode' => 'TST',
            'start' => 102,
            'end' => 104,
        ]);

        // Make the patch
        $this->actingAs($this->user, 'admin')
            ->visit($formRoute)
            ->patch($requestRoute, $data)
            ->followRedirects()
            ->seePageIs($successRoute)
            ->see($msg)
        ;

        // fetch those back.
        $vouchers = Voucher::where('currentstate', 'retired')
            ->with('history')
            ->get()
        ;

        // Check there are 3.
        $this->assertCount(3, $vouchers);

        $vouchers->each(function ($v) {
            $this->assertEquals(1, $v->history->where('to', 'voided')->count());
            $this->assertEquals(1, $v->history->where('to', 'retired')->count());
        });
    }

    /** @test */
    public function testItCanExpireVoucherCodes()
    {
        {
            // The post data
            $data = [
                'voucher-start' => 'TST0102',
                'voucher-end' => 'TST0104',
                'transition' => 'expire'
            ];

            // Set some routes
            $formRoute = route('admin.vouchers.void');
            $requestRoute = route('admin.vouchers.updatebatch');
            $successRoute = route('admin.vouchers.index');

            // Set the message to look for
            $msg = trans('service.messages.vouchers_batchtransition.success', [
                'transition_to' => 'retired',
                'shortcode' => 'TST',
                'start' => 102,
                'end' => 104,
            ]);

            // Make the patch
            $this->actingAs($this->user, 'admin')
                ->visit($formRoute)
                ->patch($requestRoute, $data)
                ->followRedirects()
                ->seePageIs($successRoute)
                ->see($msg)
            ;

            // fetch those back.
            $vouchers = Voucher::where('currentstate', 'retired')
                ->with('history')
                ->get()
            ;

            // Check there are 3.
            $this->assertCount(3, $vouchers);

            $vouchers->each(function ($v) {
                $this->assertEquals(1, $v->history->where('to', 'expired')->count());
                $this->assertEquals(1, $v->history->where('to', 'retired')->count());
            });
        }
    }
}
