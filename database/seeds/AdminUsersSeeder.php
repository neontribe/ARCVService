<?php

use Illuminate\Database\Seeder;

class AdminUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Do we need an alexandrarose seed too?
        // Charlie thinks they will never use staging - so leaving for now.
        // If we do - it will need to be anonymised.
        $usersData = [
            [
                'name' => 'Rhys Chocolate',
                'email' => 'arc+admin@neontribe.co.uk',
                'password' => bcrypt('admin_pass'),
            ],
        ];

        foreach ($usersData as $user) {
            factory(App\AdminUser::class)->create($user);
        }
    }
}
