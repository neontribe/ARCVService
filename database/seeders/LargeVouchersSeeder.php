<?php
namespace Database\Seeders;

use App\Delivery;
use App\Sponsor;
use App\StateToken;
use App\Trader;
use App\User;
use App\Voucher;
use App\VoucherState;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class LargeVouchersSeeder extends Seeder
{
    protected $states = [
        [
            'transition' => 'dispatch',
            'from' => 'printed',
            'to' => 'dispatched',
        ],
        [
            'transition' => 'collect',
            'from' => 'dispatched',
            'to' => 'recorded',
        ],
        [
            'transition' => 'confirm',
            'from' => 'recorded',
            'to' => 'payment_pending',
        ],
    ];
    /**
     * Run this seeder when we need a user with a large amount of vouchers.
     * This seeder takes longer to run so should only be used when needed.
     *
     * @return void
     */
    public function run()
    {
        // get or create the seeder user
        $user = User::where('name', 'demoseeder')->first();
        if (!$user) {
            $user = factory(User::class)->create(['name' => 'demoseeder']);
        };

        Auth::login($user);

        // Get Brian Bloom, our designated user for a lot of vouchers
        $largeUser = User::where('name', 'Brian Bloom')->first();
        $date = Carbon::now()->subMonths(3);
        // Create 1000 vouchers so Brian Bloom can request payment for them all at once
        $recordedVouchers = factory(Voucher::class, 1000)->state('printed')->create([
            'trader_id' => $largeUser->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'recorded'
        ]);

        // Make a transition definition
        $collectTransitionDef = Voucher::createTransitionDef("printed", "collect");

        VoucherState::batchInsert($recordedVouchers, $date,1, 'User', $collectTransitionDef);

        // Create 5000 vouchers that have a state of payment_pending
        $pendingVouchers = factory(Voucher::class, 5000)->state('printed')->create([
            'trader_id' => $largeUser->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'payment_pending'
        ]);

        // Make a transition definition
        $existingTransitionDef = Voucher::createTransitionDef("recorded", "confirm");

        VoucherState::batchInsert($pendingVouchers, $date,1, 'User', $existingTransitionDef);
    }
}
