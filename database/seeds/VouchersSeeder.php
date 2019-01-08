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

        // Only requested state works with seeder for now.
        // Only 40 for now or we will get 9 digits.
        $rvp_vouchers = factory(App\Voucher::class, 'requested', 40)->create();

        // Make some random vouchers.
        factory(App\Voucher::class, 'requested', 50)->create();

        $size = sizeOf($rvp_vouchers);
        // Assign the codes that match the paper.
        for ($i=60; $i<$size+60; $i++) {
            $k = $i-60;
            $rvp_vouchers[$k]->code = 'RVNT123455'.$i;
            $rvp_vouchers[$k]->sponsor_id = $rvp->id;
            $rvp_vouchers[$k]->save();

            if ($rvp_vouchers[$k]->id < 32) {
                //Progress these to printed.
                $rvp_vouchers[$k]->applyTransition('order');
                $rvp_vouchers[$k]->applyTransition('print');
            }

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
        $token = factory(App\StateToken::class)->create([
            'uuid' => 'notarealuuid',
        ]);
        for ($i=10; $i<16; $i++) {
            // Trader 3 chosen because 1 might be used for specific other stuff.
            $rvp_vouchers[$i]->trader_id = 3;
            $rvp_vouchers[$i]->applyTransition('collect');
            $rvp_vouchers[$i]->applyTransition('confirm');
            $rvp_vouchers[$i]->getPriorState()->stateToken()->associate($token);
            $rvp_vouchers[$i]->save();
            if ($i > 13) {
                $rvp_vouchers[$i]->applyTransition('payout');
            }
        }

        // 4 digit printed vouchers for trial.
        $rvnt4 = factory(App\Voucher::class, 'requested', 100)->create();
        foreach ($rvnt4 as $k => $v) {
            $v->code = 'RVNT' . str_pad($k, 4, '0', STR_PAD_LEFT);
            $v->sponsor_id = $rvp->id;
            // Progress to printed.
            $v->applyTransition('order');
            $v->applyTransition('print');
            $v->applyTransition('dispatch');
        }

    }
}
