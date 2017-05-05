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
        $sponsorData = [
            ['name' => "Real Virtual Project", "shortcode" =>"RVP"],
            ['name' => "Sponsor of Latinum", 'shortcode' => "SOL"],
        ];

        foreach ($sponsorData as $sponsor) {
            factory(App\Sponsor::class)->create($sponsor);
        }
    }
}
