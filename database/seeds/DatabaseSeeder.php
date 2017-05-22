<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

        $this->call(SponsorsSeeder::class);
        $this->call(MarketsSeeder::class);
        $this->call(TradersSeeder::class);
        // Relies on some Traders existing to make relations.
        // For now - User is API user.
        $this->call(UsersSeeder::class);
        $this->call(VouchersSeeder::class);
        $this->call(VoucherStatesSeeder::class);
    }
}
