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
        $sol = App\Sponsor::where('shortcode', 'SOL')->first();

        // Only requested state works with seeder for now.
        // Only 40 for now or we will get 9 digits.
        $rvp_vouchers = factory(App\Voucher::class, 'requested', 40)->create();

        $sol_vouchers = factory(App\Voucher::class, 'requested', 50)->create();

        $size = sizeOf($rvp_vouchers);
        // Assign the codes that match the paper.
        for ($i=60; $i<$size+60; $i++) {
            $rvp_vouchers[$i-60]->code = 'RVP123455'.$i;
            $rvp_vouchers[$i-60]->sponsor_id = $rvp->id;
            $rvp_vouchers[$i-60]->save();

            if ($rvp_vouchers[$i-60]->id < 10) {
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

        $size = sizeOf($sol_vouchers);
        // Assign the codes that match the paper.
        for ($i=10; $i<$size+10; $i++) {
            $sol_vouchers[$i-10]->code = 'SOL100000'.$i;
            $sol_vouchers[$i-10]->sponsor_id = $sol->id;
            $sol_vouchers[$i-10]->save();
        }

        /* SAVE for LATER

          for each Sponsor, instantiate *over 100* vouchers!
        $batch_size = 101;
        $sponsors = \App\Sponsor::all();

        foreach ($sponsors as $sponsor) {
            for ($i = 1; $i <= $batch_size; $i++) {
                $voucher = factory(App\Voucher::class, 'requested')->create([
                    'code' => $sponsor->shortcode . str_pad($i, 8, "0", STR_PAD_LEFT),
                    'sponsor_id' => $sponsor->id,
                ]);
                $voucher->applyTransition('order');
                $voucher->applyTransition('print');
                $voucher->applyTransition('dispatch');
                $voucher->applyTransition('allocate');
            }
        }

        // get a trader, assign first 10
        $trader = \App\Trader::find(1);
        $link_size = 10;
        for ($i=1; $i <= $link_size; $i++) {
            $voucher = App\Voucher::find($i);
            $voucher->trader_id = $trader->id;
            $voucher->applyTransition('collect');
        } */
    }

}
