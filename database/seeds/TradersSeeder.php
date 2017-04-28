<?php

use Illuminate\Database\Seeder;

class TradersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $traders = [];
        $tradersData = [
            [
                'name' => "Lord Nodens",
                'market_id'=> 1
            ],
            [
                'name' => "Byakhee Brothers",
                'market_id'=> 2
            ],
            [
                'name' => "Tracey Tyndalos",
                'market_id'=> 2
            ],
            [
                'name' => "Dave Ultramureehn",
                'market_id' => 4
            ],
            [
                'name' => "Harry Darkangel",
                'market_id' => 5
            ],
            [
                'name' => "Desmond Artifex",
                'market_id' => 5
            ],
        ];

        foreach ($tradersData as $trader) {
            $traders[] = factory(App\Trader::class)->create($trader);
        }
    }
}
