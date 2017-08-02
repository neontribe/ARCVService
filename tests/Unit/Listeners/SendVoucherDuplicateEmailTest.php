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
    protected $vouchers;
    protected $user;

    protected function setUp()
    {
        parent::setUp();
        $this->vouchers = factory(Voucher::class, 'requested', 10)->create();
        $this->user = factory(User::class)->create();
    }

    public function testVoucherDuplicateEmail()
    {
        $trader = factory(Trader::class)->create(['market_id' => factory(Market::class)->create()->id]);
        $user = $this->user;
        $market = $trader->market;
        $title = 'Test Voucher Duplicate Email';
        $voucher_codes = $this->vouchers->pluck('code')->all();
        Auth::login($user);
        $event = new VoucherDuplicateEntered($user, $trader, $voucher_codes);
        $listener = new SendVoucherDuplicateEmail();
        $listener->handle($event);

        // We can improve this - but test basic data is correct.
        $this->seeEmailWasSent()
            ->seeEmailTo(config('mail.to_admin.address'))
            ->seeEmailSubject('Voucher Duplicate Entered Email')
            ->seeEmailContains('Hi ' . config('mail.to_admin.name'))
            ->seeEmailContains($user->name . ' has tried to submit voucher')
            ->seeEmailContains(implode(', ', $voucher_codes) . ' against')
            ->seeEmailContains($trader->name . ' of')
            ->seeEmailContains($market->name . '\'s account, however that voucher has already been submitted by another trader.')
        ;
    }

    public function testVoucherDuplicateEmailNoMarket()
    {
        $trader = factory(Trader::class)->create();
        $user = $this->user;
        $title = 'Test Voucher Duplicate Email';
        $voucher_codes = $this->vouchers->pluck('code')->all();
        Auth::login($user);
        $event = new VoucherDuplicateEntered($user, $trader, $voucher_codes);
        $listener = new SendVoucherDuplicateEmail();
        $listener->handle($event);

        // We can improve this - but test basic data is correct.
        $this->seeEmailWasSent()
            ->seeEmailTo(config('mail.to_admin.address'))
            ->seeEmailSubject('Voucher Duplicate Entered Email')
            ->seeEmailContains('Hi ' . config('mail.to_admin.name'))
            ->seeEmailContains($user->name . ' has tried to submit voucher')
            ->seeEmailContains(implode(', ', $voucher_codes) . ' against')
            ->seeEmailContains($trader->name . ' of')
            ->seeEmailContains('no associated market' . '\'s account, however that voucher has already been submitted by another trader.')
        ;
    }
}
