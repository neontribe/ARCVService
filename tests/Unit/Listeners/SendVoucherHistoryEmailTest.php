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

    protected function setUp()
    {
        parent::setUp();
        $this->traders = factory(Trader::class, 2)->create();
        $this->vouchers = factory(Voucher::class, 'requested', 10)->create();
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
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
            $v->applyTransition('allocate');
            $v->trader_id = 1;
            $v->applyTransition('collect');
        }

        // Progress one to pending_payment.
        $this->vouchers[0]->applyTransition('confirm');

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
        Auth::login($this->user);
        $controller = new TraderController();
        $file = $controller->createVoucherHistoryFile($this->traders[0]);

        $event = new VoucherHistoryEmailRequested($this->user, $file);
        $listener = new SendVoucherHistoryEmail();
        $listener->handle($event);
    }
}
