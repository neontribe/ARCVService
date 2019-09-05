<?php

use App\Centre;
use App\Delivery;
use App\User;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

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

        // Get the Centre Ids in random order
        $centre_ids = Centre::pluck('id')->toArray();
        shuffle($centre_ids);

        // Setup 5 deliveries to random centres and add 50 vouchers to each
        factory(App\Delivery::class, 5)
            ->create([
                // pop the last one off.
                'centre_id' => function () use (&$centre_ids) {
                    return array_pop($centre_ids);
                },
                'dispatched_at' => function () {
                    return Carbon::today()
                        ->subDays(rand(0, 30))
                        ->subMonths(rand(0, 11));
                }
            ])
            ->each(function (Delivery $delivery) {
                // get the shortcode
                $shortcode = $delivery->centre->sponsor->shortcode;
                // make a code range
                $codes = Voucher::generateCodeRange($shortcode . "001000", $shortcode . "001050");
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
                $delivery->range = $shortcode . "001000" . " - " . $shortcode . "001050";
                $delivery->save();
            });
    }
}