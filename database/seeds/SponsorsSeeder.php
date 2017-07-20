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
        $sponsor = ['name' => "Real Virtual Project", "shortcode" =>"RVNT"];
        factory(App\Sponsor::class)->create($sponsor);

        // And 4 default factory models.
        //Commented out for Demo purposes
        //factory(App\Sponsor::class, 4)->create();
    }
}
