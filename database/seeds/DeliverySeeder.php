<?php

use App\Centre;
use App\Delivery;
use App\User;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DeliverySeeder extends Seeder
{
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

        // Create three random vouchers and transition to printed, then bundle
        /** @var Collection $vs */
        $vs1 = factory(Voucher::class, 'requested', 3)
            ->create()
            ->each(function (Voucher $v) {
                $v->applyTransition('order');
                $v->applyTransition('print');
                $v->applyTransition('dispatch');
            });


        // Get the Centre Ids in random order
        $centre_ids = Centre::pluck('id')->toArray();
        shuffle($centre_ids);

        // Setup 5 deliveries to random centres and add 50 vouchers to each
        $deliveries = factory(App\Delivery::class, 5)
            ->create([
                // pop the last one off.
                'centre_id' => function () use (&$centre_ids) {
                    return array_pop($centre_ids);
                },
            ])
            ->each(function (Delivery $delivery) {
                // get the shortcode
                $shortcode = $delivery->centre->sponsor->shortcode;
                // make a code range
                $codes = Voucher::generateCodeRange($shortcode. "001000", $shortcode. "001050");
                // turn those into vouchers
                $vs = factory(Voucher::class, 'requested', count($codes))
                    ->create([
                        'code' => function () use (&$codes) {
                            return array_pop($codes);
                        },
                    ])
                    ->each(function (Voucher $v) {
                        $v->applyTransition('order');
                        $v->applyTransition('print');
                        $v->applyTransition('dispatch');
                    });
                $delivery->vouchers()->saveMany($vs);
                $delivery->range = $shortcode. "001000" . " - " . $shortcode. "001050";
                $delivery->save();
            });
    }
}