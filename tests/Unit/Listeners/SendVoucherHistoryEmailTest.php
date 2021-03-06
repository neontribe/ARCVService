<?php

namespace Tests\Unit\Listeners;

use App\Events\VoucherHistoryEmailRequested;
use App\Http\Controllers\API\TraderController;
use App\Listeners\SendVoucherHistoryEmail;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\User;
use App\Voucher;
use App\VoucherState;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spinen\MailAssertions\MailTracking;
use Tests\TestCase;

class SendVoucherHistoryEmailTest extends TestCase
{
    use DatabaseMigrations;
    use MailTracking;

    protected $traders;
    protected $vouchers;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traders = factory(Trader::class, 2)->create();
        $this->vouchers = factory(Voucher::class, 'printed', 10)->create();
        $this->user = factory(User::class)->create();

        // Add market to trader[1];
        // We currently require markets to be created with sponsors.
        // TODO In our model is nullable - so noted in Tech Debt.
        $this->traders[1]->market_id = factory(Market::class)->create([
            'sponsor_id' => factory(Sponsor::class)->create()->id,
        ])->id;
        $this->traders[1]->save();

        // Set up voucher states.
        Auth::login($this->user);

        foreach ($this->vouchers as $v) {
            $v->applyTransition('dispatch');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }

        $this->vouchers[0]->applyTransition('confirm');
        VoucherState::where('voucher_id', $this->vouchers[0]->id)
            ->update(['created_at' => Carbon::tomorrow()]);

        // Progress a couple to reimbursed.
        // For now they display same as pending.
        $this->vouchers[1]->applyTransition('confirm');
        $this->vouchers[1]->applyTransition('payout');
        $this->vouchers[2]->applyTransition('confirm');
        $this->vouchers[2]->applyTransition('payout');


        // A voucher not belonging to trader 1.
        $this->vouchers[9]->trader_id = 2;
        $this->vouchers[9]->save();
    }

    public function testRequestVoucherHistoryEmail()
    {
        // Todo this test could be split up and improved.
        $user = $this->user;
        $trader = $this->traders[0];
        $vouchers = $trader->vouchersConfirmed;
        $title = 'Test Rose Voucher Payment Records';

        Auth::login($user);
        $controller = new TraderController();
        $file = $controller->createVoucherListFile($trader, $vouchers, $title);

        list($min_date, $max_date) = Voucher::getMinMaxVoucherDates($vouchers);
        $event = new VoucherHistoryEmailRequested($user, $trader, $file, $min_date, $max_date);
        $listener = new SendVoucherHistoryEmail();
        $listener->handle($event);

        // We can improve this - but test basic data is correct.
        // uses laravel helper function e() to prevent errors from names with apostrophes
        $this->seeEmailWasSent()
            ->seeEmailTo($user->email)
            ->seeEmailSubject('Rose Voucher Payment Records')
            ->seeEmailContains('Hi ' . e($user->name))
            ->seeEmailContains('requested a copy of ' . e($trader->name))
            ->seeEmailContains("The file includes payment records from $min_date to $max_date")
        ;
    }
}
