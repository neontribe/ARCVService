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
        factory(App\CentreUser::class)->create([
            "name"  => "ARC CC User",
            "email" => "arc+ccuser@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "centre_id" => 1,
            "role" => "centre_user",
        ]);

        factory(App\CentreUser::class)->create([
            "name"  => "ARC FM User",
            "email" => "arc+fmuser@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "centre_id" => 1,
            "role" => "foodmatters_user",
        ]);

        // ARC admin is an fmuser in centre 2, which has individual forms on the dashboard.
        factory(App\CentreUser::class)->create([
            "name"  => "ARC Admin User",
            "email" => "arc+admin@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "centre_id" => 2,
            "role" => "foodmatters_user",
        ]);

        // 1 faked user not associated with a random Centre
        factory(App\CentreUser::class)->create();

        // 3 faked users associated with random Centres
        factory(App\CentreUser::class, 3)->states('withRandomCentre')->create();
    }
}
