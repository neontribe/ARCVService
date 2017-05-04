<?php

use Illuminate\Database\Seeder;

class MarketsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Some without 'location' string will get a post code from faker.
        $marketsData = [
            [
                'name' => "Cho-Cho Retail Park",
                'location' => "Plateau of Leng",
                'sponsor_id'=> 2
            ],
            [
                'name' => "RightStars Commercial District",
                'location' => "Sunken R'yleh",
                'sponsor_id'=> 2
            ],
            [
                'name' => "Angle-Time Street Market",
                'sponsor_id'=> 2
            ],
            [
                'name' => "Hive Primus Community Jamboree",
                'location'=> "Necromunda",
                'sponsor_id' => 1
            ],
            [
                'name' => "Mechanicum Carboot",
                'sponsor_id' => 1
            ],
        ];

        foreach ($marketsData as $market) {
            factory(App\Market::class)->create($market);
        }
    }
}
