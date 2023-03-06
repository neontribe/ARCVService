<?php
namespace Database\Seeders;

use App\Trader;
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
                'name' => "Fred's Fruit and Veg",
                'market_id'=> 1,
            ],
            [
                'name' => "Larry's Local Larder",
                'market_id'=> 2,
            ],
            [
                'name' => "The Happy Fruit Company",
                'market_id'=> 2,
            ],
            [
                'name' => "Red's Tomato Trader",
                'market_id' => 4,
            ],
            [
                'name' => "Mr Bear's Apples and Pears",
                'market_id' => 5,
            ],
            [
                'name' => "Sally's Fruit Stall",
                'market_id' => 5,
            ],
            [
                'name' => "Jane's Farm Produce",
                'market_id' => 6
            ],
        ];

        foreach ($tradersData as $trader) {
            factory(Trader::class)->create($trader);
        }
    }
}
