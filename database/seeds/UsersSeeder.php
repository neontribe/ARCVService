<?php

use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $usersData = [
            [
                'name' => 'Rolf Billabong',
                'email' => 'arc+rolf@neontribe.co.uk',
                'password' => bcrypt('market_pass'),
            ],
            [
                'name' => 'Matilda Billabong',
                'email' => 'arc+tilly@neontribe.co.uk',
                'password' => bcrypt('market_pass'),
            ],
            [
                'name' => 'Kenneth Furbanks',
                'email' => 'arc+ken@neontribe.co.uk',
                'password' => bcrypt('market_pass'),
            ],
            [
                'name' => 'Greta Furbanks',
                'email' => 'arc+greta@neontribe.co.uk',
                'password' => bcrypt('market_pass'),
            ],
        ];

        foreach ($usersData as $user) {
            $users[] = factory(App\User::class)->create($user);
        }

        $traders = App\Trader::pluck('id')->toArray();
        foreach ($users as $user) {
            // Sync to a random subset of 1 2 or 3 traders.
            // Unique subset of traders.
            $id_keys = array_rand($traders, rand(1,3));
            // Sometimes returns a single int rather than array.
            if (!is_array($id_keys)) {
                $id_keys = [$id_keys];
            }
            // Now get id values rather than keys.
            $trade_ids = [];
            foreach ($id_keys as $key) {
                $trade_ids[] = $traders[$key];
            }
            $user->traders()->sync($trade_ids);
        }

        // So we reliably have one user with a single trader.
        $trader = App\Trader::find(1);
        $trader->traders()->sync([1]);
    }
}
