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


        // Get the Centre Ids
        $centre_ids = Centre::pluck('id')->toArray();

        // Setup 5 deliveries to random centres and add 100-1000 vouchers
        $deliveries = factory(Delivery::class, 5)->create([
            'centre_id' => array_random($centre_ids)
        ])->each(function (Delivery $delivery) {
            $vs = factory(Voucher::class, 'requested', rand(100, 1000))
                ->make()
                ->each(function (Voucher $v) {
                    $v->applyTransition('order');
                    $v->applyTransition('print');
                    $v->applyTransition('dispatch');
                });
            $delivery->vouchers()->saveMany($vs);
        });
    }
}