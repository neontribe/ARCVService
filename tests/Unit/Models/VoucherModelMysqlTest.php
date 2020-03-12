<?php

namespace Tests\Unit\Models;

use App\User;
use App\Sponsor;
use App\Voucher;
use Auth;
use Config;
use Exception;
use Tests\CreatesApplication;
use Tests\StoreTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class VoucherModelMysqlTest extends StoreTestCase
{
    use CreatesApplication;
    use DatabaseMigrations;

    protected $user;
    protected $rangeCodes;
    protected $vouchers;
    protected $sponsor;

    // TODO : Consider pulling this out to a config option or environment variable
    private const TESTING_MYSQL_FALLBACK = 'testing-mysql';

    public function setUp()
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
    }

    /** @test */
    public function testItCanGetVoidableVouchersByShortcode()
    {
        // Make vouchers from them
        foreach ($this->rangeCodes as $rangeCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
            $voucher->applyTransition('dispatch');
        }

        // Make a range to check
        $inBoundsRange = Voucher::createRangeDefFromVoucherCodes('TST0102', 'TST0104');

        // Check it.
        $ranges = Voucher::getVoidableVoucherRangesByShortCode($inBoundsRange->shortcode);
        $this->assertCount(3, $ranges);
        $this->assertEquals(101, $ranges[0]->start);
        $this->assertEquals(105, $ranges[0]->end);
        $this->assertEquals(201, $ranges[1]->start);
        $this->assertEquals(205, $ranges[1]->end);
        $this->assertEquals(301, $ranges[2]->start);
        $this->assertEquals(305, $ranges[2]->end);
    }

    /** @test */
    public function testItCanGetDeliverableVouchersByShortcode()
    {
        // Make vouchers from them
        foreach ($this->rangeCodes as $rangeCode) {
            $voucher = factory(Voucher::class, 'requested')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
            $voucher->applyTransition('order');
            $voucher->applyTransition('print');
        }

        // Make a range to check
        $inBoundsRange = Voucher::createRangeDefFromVoucherCodes('TST0102', 'TST0104');

        // Check it.
        $ranges = Voucher::getDeliverableVoucherRangesByShortCode($inBoundsRange->shortcode);
        $this->assertCount(3, $ranges);
        $this->assertEquals(101, $ranges[0]->start);
        $this->assertEquals(105, $ranges[0]->end);
        $this->assertEquals(201, $ranges[1]->start);
        $this->assertEquals(205, $ranges[1]->end);
        $this->assertEquals(301, $ranges[2]->start);
        $this->assertEquals(305, $ranges[2]->end);
    }
}