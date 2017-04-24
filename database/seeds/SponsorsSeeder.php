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
            ['name' => "thelaundry.gov.uk"],
            ['name' => "The Dark Future Imperium PLC"],
            ['name' => "CthulhuShire County Council"]
        ];

        foreach ($sponsorData as $sponsor) {
            $sponsors[] = factory(App\Sponsor::class)->create($sponsor);
        }
    }
}
