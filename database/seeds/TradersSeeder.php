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
        $tradersData = [
            [
                'name' => "Hector Hopinsett",
                'market_id'=> 1,
            ],
            [
                'name' => "Kristy Corntop",
                'market_id'=> 2,
            ],
            [
                'name' => "Daisy Buttercup",
                'market_id'=> 2,
            ],
            [
                'name' => "Hazel Pickleweed",
                'market_id' => 4,
            ],
            [
                'name' => "Barry Thistlethorn",
                'market_id' => 5,
            ],
            [
                'name' => "Fern Timbertop",
                'market_id' => 5,
            ],
        ];

        foreach ($tradersData as $trader) {
            factory(App\Trader::class)->create($trader);
        }
    }
}
