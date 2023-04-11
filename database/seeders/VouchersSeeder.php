<?php
namespace Database\Seeders;

use App\Delivery;
use App\Sponsor;
use App\StateToken;
use App\User;
use App\Voucher;
use App\VoucherState;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class VouchersSeeder extends Seeder
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
     * Run the database seeds.
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

        // Create RVNT one as id 1 our trader will be affiliated with this.
        $rvp = Sponsor::where('shortcode', 'RVNT')->first();

        // Only 40 for now or we will get 9 digits.
        // These vouchers dispatched before we added delivery to system.
        // They can be collected even though they do not have a delivery_id.
        $timestamp_pre_delivery = Carbon::parse(config('arc.first_delivery_date'))->subMonth(1);
        $rvp_vouchers = factory(Voucher::class, 40)->state('printed')->create([
            'created_at' => $timestamp_pre_delivery,
        ]);

        // Make some random vouchers.
        factory(Voucher::class, 50)->state('printed')->create();

        $size = sizeOf($rvp_vouchers);
        // Assign the codes that match the paper.
        for ($i=60; $i<$size+60; $i++) {
            $k = $i-60;
            $rvp_vouchers[$k]->code = 'RVNT123455'.$i;
            $rvp_vouchers[$k]->sponsor_id = $rvp->id;
            $rvp_vouchers[$k]->save();

            if ($rvp_vouchers[$k]->id < 22) {
                //Progress these to dispatched.
                $rvp_vouchers[$k]->applyTransition('dispatch');
            }

        }

        // Transition one voucher to recorded by trader 1.
        $rvp_vouchers[1]->trader_id = 1;
        $rvp_vouchers[1]->applyTransition('collect');

        // Progress a few vouchers to reimbursed and create test payment link.
        // Should be 6 vouchers RVNT12345570-75.
        // Generate a state token - for payout of a few of them.
        $paidtoken = factory(StateToken::class)->create([
            'uuid' => 'auuidforpaidvouchers',
            'admin_user_id' => '1',
        ]);
        $pendingtoken = factory(StateToken::class)->create([
            'uuid' => 'auuidforpendingvouchers',
        ]);
        for ($i=10; $i<16; $i++) {
            // Trader 3 chosen because 1 might be used for specific other stuff.
            $rvp_vouchers[$i]->trader_id = 3;
            $rvp_vouchers[$i]->applyTransition('collect');
            $rvp_vouchers[$i]->applyTransition('confirm');
            if ($i > 13) {
                $rvp_vouchers[$i]->getPriorState()->stateToken()->associate($paidtoken)->save();
                // If it matters that these had the state token, progress in seperate loop.
                // In fact - might be able to simplify by manually assigning token id to record.
                $rvp_vouchers[$i]->applyTransition('payout');
            } else {
              $rvp_vouchers[$i]->getPriorState()->stateToken()->associate($pendingtoken)->save();
            }
        }

        // 4 digit printed vouchers for trial.
        $rvnt4 = factory(Voucher::class, 100)->state('printed')->create();
        foreach ($rvnt4 as $k => $v) {
            $v->code = 'RVNT' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $v->sponsor_id = $rvp->id;
            // Progress to dispatched.
            $v->applyTransition('dispatch');
        }

        // Generate a corresponding delivery
        $delivery = factory(Delivery::class)->create([
            // Our sponsor 'RVNT' is definitely linked to this centre
            'centre_id' => 1,
            'dispatched_at' => Carbon::now()->subMonth(1),
            'range' => 'RVNT0000-RVNT0099',
        ]);
        $delivery->vouchers()->saveMany($rvnt4);

        $user = User::where('name', 'Rolf Billabong')->first();
        $date = Carbon::now()->subMonths(3);
        // Create 50 vouchers that have a state of payment_pending
        // and attach to Rolf Billabong
        factory(Voucher::class, 50)->state('printed')->create([
            'trader_id' => $user->traders[0]->id,
            'created_at' => $date,
            'updated_at' => $date,
            'currentstate' => 'payment_pending'
        ])->each(function ($v) use (&$date) {
            // add a day to it
            $date->addDay();
            foreach ($this->states as $state) {
                $base = [
                    'voucher_id' => $v->id,
                    'created_at' => $date->addSeconds(10)->format('Y-m-d H:i:s'),
                ];
                $attribs = array_merge($base, $state);
                factory(VoucherState::class)->create($attribs);
            }
        });
    }
}
