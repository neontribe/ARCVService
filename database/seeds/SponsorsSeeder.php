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
        $sponsors = [];
        $sponsorData = [
            ['name' => "imperium.gov", "shortcode" =>"SOL"],
            ['name' => "CthulhuShire County Council", 'shortcode' => "CCC"]
        ];

        foreach ($sponsorData as $sponsor) {
            $sponsors[] = factory(App\Sponsor::class)->create($sponsor);
        }
    }
}
