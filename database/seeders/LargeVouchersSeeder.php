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
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class LargeVouchersSeeder extends Seeder
{
    protected array $states = [
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
    public function run(): void
    {
        // get or create the seeder user
        $user = User::where('name', 'demoseeder')->first();
        if (!$user) {
            $user = factory(User::class)->create(['name' => 'demoseeder']);
        };

        Auth::login($user);

        // Get Brian Bloom, our designated user for a lot of vouchers
        $largeUser = User::where('name', 'Brian Bloom')->first();
        // 2 sub-months a 3 could push it outside the 90 day history filter
        $date = Carbon::now()->subMonths(2);
        // Create 1000 vouchers so Brian Bloom can request payment for them all at once
        $recordedVouchers = factory(Voucher::class, 1000)->state('printed')->create([
            'trader_id' => $largeUser->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'recorded'
        ]);

        // Make a transition definition
        $collectTransitionDef = Voucher::createTransitionDef("printed", "collect");

        VoucherState::batchInsert($recordedVouchers, $date, 1, 'User', $collectTransitionDef);

        // Create 5000 vouchers that have a state of payment_pending
        $pendingVouchers = factory(Voucher::class, 5000)->state('printed')->create([
            'trader_id' => $largeUser->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'payment_pending'
        ]);

        // Make a transition definition
        $existingTransitionDef = Voucher::createTransitionDef("recorded", "confirm");

        VoucherState::batchInsert($pendingVouchers, $date, 1, 'User', $existingTransitionDef);

        // Create 5000 vouchers that have a state of reimbursed
        $reimbursedVouchers = factory(Voucher::class, 5000)->state('printed')->create([
            'trader_id' => $largeUser->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'payment_pending'
        ]);
        // Make a transition definition
        $reimbursedTransitionDef = Voucher::createTransitionDef("payment_pending", "payout");
        VoucherState::batchInsert($reimbursedVouchers, $date, 1, 'User', $reimbursedTransitionDef);

        // To make the vouchers usable to test MVL export we need random created dates but batch insert forces the date
        // to be now
        // THIS DOES NOT SEEM TO WORK, use this SQL instead see docs/README.md
        /*
        $faker = Factory::create();
        $voucherStates = VoucherState::where("to", "=", "reimbursed")->get();
        print "Got vouchers " . count($voucherStates) . "\n";
        foreach ($voucherStates as $vs) {
            $createdAt = $faker->dateTimeBetween("-3 years")->format('Y-m-d H:i:s');
            $vs->created_at = $createdAt;
            print "Voucher " . $vs->id . ", date: " . $createdAt . "\n";
            $vs->save();
        }
        */
    }
}
