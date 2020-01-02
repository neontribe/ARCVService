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
        // Pick a seed scenario
        switch (config('app.seeds')) {
            case "Usability":
                $this->usabilitySeeds();
                break;
            case "Dev":
            default:
                $this->devSeeds();
                break;
        }
    }

    /**
     * Standard scenario for development
     */
    public function devSeeds()
    {
        $this->call(SponsorsSeeder::class);
        $this->call(CentresSeeder::class);
        $this->call(CentreUsersSeeder::class);
        $this->call(RegistrationsSeeder::class);
        $this->call(MarketsSeeder::class);
        $this->call(TradersSeeder::class);
        // Relies on some Traders existing to make relations.
        // For now - User is API user.
        $this->call(UsersSeeder::class);
        $this->call(AdminUsersSeeder::class);
        $this->call(VouchersSeeder::class);
        $this->call(VoucherStatesSeeder::class);
        $this->call(BundleSeeder::class);
        $this->call(DeliverySeeder::class);
        $this->call(TestActiveUsersSeeder::class);
    }

    /**
     * Specific scenario for usability testing
     */
    public function usabilitySeeds()
    {
        $this->call(SponsorsSeeder::class);
        $this->call(UsabilityScenarioSeeder::class);

        // Needed for voucher seeder
        $this->call(MarketsSeeder::class);
        $this->call(TradersSeeder::class);

        $this->call(VouchersSeeder::class);
        $this->call(VoucherStatesSeeder::class);
        $this->call(BundleSeeder::class);
    }
}