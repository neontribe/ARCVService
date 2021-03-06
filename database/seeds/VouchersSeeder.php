<?php

use Illuminate\Database\Seeder;

class VouchersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // get or create the seeder user
        $user = App\User::where('name', 'demoseeder')->first();
        if (!$user) {
            $user = factory(App\User::class)->create(['name' => 'demoseeder']);
        };

        Auth::login($user);

        // Create RVNT one as id 1 our trader will be affiliated with this.
        $rvp = App\Sponsor::where('shortcode', 'RVNT')->first();

        // Only 40 for now or we will get 9 digits.
        // These vouchers dispatched before we added delivery to system.
        // They can be collected even though they do not have a delivery_id.
        $timestamp_pre_delivery = Carbon\Carbon::parse(config('arc.first_delivery_date'))->subMonth(1);
        $rvp_vouchers = factory(App\Voucher::class, 'printed', 40)->create([
            'created_at' => $timestamp_pre_delivery,
        ]);

        // Make some random vouchers.
        factory(App\Voucher::class, 'printed', 50)->create();

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
        $paidtoken = factory(App\StateToken::class)->create([
            'uuid' => 'auuidforpaidvouchers',
        ]);
        $pendingtoken = factory(App\StateToken::class)->create([
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
        $rvnt4 = factory(App\Voucher::class, 'printed', 100)->create();
        foreach ($rvnt4 as $k => $v) {
            $v->code = 'RVNT' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $v->sponsor_id = $rvp->id;
            // Progress to dispatched.
            $v->applyTransition('dispatch');
        }

        // Generate a corresponding delivery
        $delivery = factory(App\Delivery::class)->create([
            // Our sponsor 'RVNT' is definitely linked to this centre
            'centre_id' => 1,
            'dispatched_at' => Carbon\Carbon::now()->subMonth(1),
            'range' => 'RVNT0000-RVNT0099',
        ]);
        $delivery->vouchers()->saveMany($rvnt4);
    }
}
