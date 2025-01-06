<?php

namespace Tests\Unit\Models;

use App\User;
use App\Sponsor;
use App\Voucher;
use Auth;
use Tests\CreatesApplication;
use Tests\MysqlStoreTestCase;

class VoucherModelMysqlTest extends MysqlStoreTestCase
{
    use CreatesApplication;

    protected $user;
    protected array $rangeCodes;
    protected $vouchers;
    protected $sponsor;

    public function setUp(): void
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

        $this->user = factory(User::class)->create();
        Auth::login($this->user);
    }

    public function testItCanGetDeliverableVouchersByShortcode(): void
    {
        // Make vouchers from them
        foreach ($this->rangeCodes as $rangeCode) {
            factory(Voucher::class)->state('printed')->create([
                'code' => $rangeCode,
                'sponsor_id' => $this->sponsor->id,
            ]);
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
