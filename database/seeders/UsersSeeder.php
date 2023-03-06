<?php
namespace Database\Seeders;

use App\User;
use App\Trader;
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
            $users[] = factory(User::class)->create($user);
        }

        $traders = Trader::pluck('id')->toArray();
        foreach ($users as $user) {
            // Sync to a random subset of 1 2 or 3 traders.
            // Unique subset of traders.
            $id_keys = array_rand($traders, rand(1, 3));
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
        $users[0]->traders()->sync([1]);

        // So we reliably have one user who can act on behalf of all traders.
        $users[3]->traders()->sync($traders);

        // So we reliably have a specific user who cannot access the tap page
        $users[1]->traders()->sync($traders[6]);
    }
}
