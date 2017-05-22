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
                'name' => "Beechwood Hall",
                'location' => "Sylvania",
                'sponsor_id'=> 2
            ],
            [
                'name' => "Sea Breeze Cape",
                'location' => "Secret Island",
                'sponsor_id'=> 2
            ],
            [
                'name' => "Fruit Wagon",
                'sponsor_id'=> 2
            ],
            [
                'name' => "Brick Oven Bakery",
                'location'=> "Cloverleaf Corners",
                'sponsor_id' => 1
            ],
            [
                'name' => "Cedar Terrace",
                'sponsor_id' => 1
            ],
        ];

        foreach ($marketsData as $market) {
            factory(App\Market::class)->create($market);
        }
    }
}
