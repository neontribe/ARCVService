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
        $sponsor = ['name' => "Real Virtual Project", "shortcode" =>"RVP"];
        factory(App\Sponsor::class)->create($sponsor);

        // And 4 default factory models.
        factory(App\Sponsor::class, 4)->create();
    }
}
