<?php

use Illuminate\Database\Seeder;

class SponsorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //create RVNT sponsor for tests
        $sponsor = ['name' => "Real Virtual Project", "shortcode" =>"RVNT"];
        factory(App\Sponsor::class)->create($sponsor);

        // And 5 default factory models to be able to mirror live data
        factory(App\Sponsor::class, 5)->create();
    }
}
