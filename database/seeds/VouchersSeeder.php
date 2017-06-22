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

        // Create RVP one as id 1 our trader will be affiliated with this.
        $rvp = App\Sponsor::where('shortcode', 'RVP')->first();

        // Only requested state works with seeder for now.
        // Only 40 for now or we will get 9 digits.
        $rvp_vouchers = factory(App\Voucher::class, 'requested', 40)->create();

        // Make some random vouchers.
        factory(App\Voucher::class, 'requested', 50)->create();

        $size = sizeOf($rvp_vouchers);
        // Assign the codes that match the paper.
        for ($i=60; $i<$size+60; $i++) {
            $rvp_vouchers[$i-60]->code = 'RVP123455'.$i;
            $rvp_vouchers[$i-60]->sponsor_id = $rvp->id;
            $rvp_vouchers[$i-60]->save();

            if ($rvp_vouchers[$i-60]->id < 32) {
                //Progress these to allocated.
                $rvp_vouchers[$i-60]->applyTransition('order');
                $rvp_vouchers[$i-60]->applyTransition('print');
                $rvp_vouchers[$i-60]->applyTransition('dispatch');
                $rvp_vouchers[$i-60]->applyTransition('allocate');
            }
        }

        // Transition one voucher to recorded by trader 1.
        $rvp_vouchers[1]->trader_id = 1;
        $rvp_vouchers[1]->applyTransition('collect');
    }
}
