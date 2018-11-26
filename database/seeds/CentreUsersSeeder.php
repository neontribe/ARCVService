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

        // Attach an initial centre
        $user1->centres()->attach([
            1 => ['homeCentre' => true]
        ]);

        // Get the first centre's sponsor and make two more centres with the same sponsor
        $sponsor_id = $user1->centres()->first()->sponsor->id;

        $local_centres = factory(App\Centre::class, 2)->create(['sponsor_id' => $sponsor_id]);

        // Attach the extra centres
        $user1->centres()->attach([
            $local_centres[0]->id  => ['homeCentre' => false],
            $local_centres[1]->id  => ['homeCentre' => false],
        ]);

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
        $user3->centres()->attach(2, ['homeCentre' => true]);

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
