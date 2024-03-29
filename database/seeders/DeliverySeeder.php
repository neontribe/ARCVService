<?php
namespace Database\Seeders;

use App\Centre;
use App\Delivery;
use App\User;
use App\Voucher;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

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
        factory(Delivery::class, 5)
            ->create([
                // pop the last one off.
                'centre_id' => function () use (&$centre_ids) {
                    return array_pop($centre_ids);
                },
                // check the date
                'dispatched_at' => function () {
                    return Carbon::today()
                        ->subMonths(rand(0, 11))
                        ->subDays(rand(0, 30));
                },
            ])
            ->each(function (Delivery $delivery, $key) {
                // Get the shortcode
                $shortcode = $delivery->centre->sponsor->shortcode;
                // Make a code range
                $codes = Voucher::generateCodeRange(
                    $shortcode . "01" . $key . "000",
                    $shortcode . "01" . $key . "050"
                );
                // Turn those into vouchers
                $vs = factory(Voucher::class, count($codes))->state('printed')
                    ->create([
                        'code' => function () use (&$codes) {
                            return array_pop($codes);
                        },
                    ])
                    ->each(function (Voucher $v) {
                        $v->applyTransition('dispatch');
                    });
                $delivery->vouchers()->saveMany($vs);
                $delivery->range = $shortcode . "01" . $key ."000" . " - " . $shortcode . "01" . $key . "050";
                $delivery->save();
            });
    }
}
