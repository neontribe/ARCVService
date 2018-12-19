<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherDuplicateEntered;
use App\Listeners\SendVoucherDuplicateEmail;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\User;
use App\Voucher;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spinen\MailAssertions\MailTracking;
use Tests\TestCase;

class SendVoucherDuplicateEmailTest extends TestCase
{
    use DatabaseMigrations;
    use MailTracking;

    protected $trader;
    protected $voucher;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->voucher = factory(Voucher::class)->create();
        $this->user = factory(User::class)->create();
    }

    public function testVoucherDuplicateEmail()
    {
        $trader = factory(Trader::class)->create(['market_id' => factory(Market::class)->create()->id]);
        $user = $this->user;
        $vouchercode = $this->voucher->code;
        $market = $trader->market;
        $title = 'Test Voucher Duplicate Email';

        Auth::login($user);
        $event = new VoucherDuplicateEntered($user, $trader, $this->voucher);
        $listener = new SendVoucherDuplicateEmail();
        $listener->handle($event);

        // We can improve this - but test basic data is correct.
        // uses laravel helper function e() to prevent errors from names with apostrophes
        $this->seeEmailWasSent()
            ->seeEmailTo(config('mail.to_admin.address'))
            ->seeEmailSubject('Voucher Duplicate Entered Email')
            ->seeEmailContains('Hi ' . config('mail.to_admin.name'))
            ->seeEmailContains(e($user->name) . ' has tried to submit voucher')
            ->seeEmailContains($vouchercode . ' against')
            ->seeEmailContains(e($trader->name) . ' of')
            ->seeEmailContains(e($market->name). '\'s account, however that voucher has already been submitted by another trader.')
        ;
    }

    public function testVoucherDuplicateEmailNoMarket()
    {
        $trader = factory(Trader::class)->create();
        $user = $this->user;
        $vouchercode = $this->voucher->code;
        $title = 'Test Voucher Duplicate Email';

        Auth::login($user);
        $event = new VoucherDuplicateEntered($user, $trader, $this->voucher);
        $listener = new SendVoucherDuplicateEmail();
        $listener->handle($event);

        // We can improve this - but test basic data is correct.
        // uses laravel helper function e() to prevent errors from names with apostrophes
        $this->seeEmailWasSent()
            ->seeEmailTo(config('mail.to_admin.address'))
            ->seeEmailSubject('Voucher Duplicate Entered Email')
            ->seeEmailContains('Hi ' . config('mail.to_admin.name'))
            ->seeEmailContains(e($user->name) . ' has tried to submit voucher')
            ->seeEmailContains($vouchercode . ' against')
            ->seeEmailContains(e($trader->name) . ' of')
            ->seeEmailContains('no associated market' . '\'s account, however that voucher has already been submitted by another trader.')
        ;
    }
}
