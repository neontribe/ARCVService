<?php

use Illuminate\Database\Seeder;

class CentreUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1 specific user in the first centre
        $user1 = factory(App\CentreUser::class)->create([
            "name"  => "ARC CC User",
            "email" => "arc+ccuser@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "role" => "centre_user",
        ]);
        $user1->centres()->attach(1, ['homeCentre' => true]);

        $user2 = factory(App\CentreUser::class)->create([
            "name"  => "ARC FM User",
            "email" => "arc+fmuser@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "role" => "foodmatters_user",
        ]);
        $user2->centres()->attach(1, ['homeCentre' => true]);

        // ARC admin is an fmuser in centre 2, which has individual forms on the dashboard.
        $user3 = factory(App\CentreUser::class)->create([
            "name"  => "ARC Admin User",
            "email" => "arc+admin@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "role" => "foodmatters_user",
        ]);
        $user3->centres()->attach(1, ['homeCentre' => true]);

        // 1 faked user not associated with a random Centre
        factory(App\CentreUser::class)->create();

        // 3 faked users associated with random Centres
        factory(App\CentreUser::class, 3)
            ->create()
            ->each(function ($centreUser) {
                $centres  = App\Centre::get();
                if ($centres->count() > 0) {
                    // Pick a random Centre
                    $centre = $centres[random_int(0, $centres->count()-1)];
                } else {
                    // There should be at least one Centre
                    $centre = factory(App\Centre::class)->create();
                }
                $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
            });
    }
}
