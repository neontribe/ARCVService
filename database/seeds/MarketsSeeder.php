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
                'sponsor_id' => 1,
                'payment_message' => 'Please return your vouchers to the office.',
            ],
            [
                'name' => "Sea Breeze Cape",
                'location' => "Secret Island",
                'sponsor_id' => 2,
                'payment_message' => 'Please mark your vouchers with the current date and return to the office.',
            ],
            [
                'name' => "Fruit Wagon",
                'payment_message' => 'Please drop your vouchers off at the office.',
            ],
            [
                'name' => "Brick Oven Bakery",
                'location'=> "Cloverleaf Corners",
                'payment_message' => 'Please post your vouchers to the office.',
            ],
            [
                'name' => "Cedar Terrace",
                'payment_message' => 'Please mark your vouchers with the stall name and return to the office.',
            ],
            [
                'name' => "No Tap Farm",
                'location' => 'Happy Farm',
                'sponsor_id' => 7,
                'payment_message' => 'Please post your vouchers to the office.',
            ],
        ];

        foreach ($marketsData as $market) {
            factory(App\Market::class)->create($market);
        }
    }
}
