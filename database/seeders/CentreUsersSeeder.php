<?php
namespace Database\Seeders;

use App\Centre;
use App\CentreUser;
use Illuminate\Database\Seeder;

class CentreUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1 specific user in the first centre
        $user1 = factory(CentreUser::class)->create([
            "name"  => "ARC CC User",
            "email" => "arc+ccuser@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
            "role" => "centre_user",
        ]);

        // Attach an initial centre
        $user1->centres()->attach([
            1 => ['homeCentre' => true]
        ]);

        // Get the first centre's sponsor and make two more centres with the same sponsor
        $sponsor_id = $user1->centres()->first()->sponsor->id;

        $local_centres = factory(Centre::class, 2)->create(['sponsor_id' => $sponsor_id]);

        // Attach the extra centres
        $user1->centres()->attach([
            $local_centres[0]->id  => ['homeCentre' => false],
            $local_centres[1]->id  => ['homeCentre' => false],
        ]);

        $user2 = factory(CentreUser::class)->state('FMUser')->create([
            "name"  => "ARC FM User",
            "email" => "arc+fmuser@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
        ]);
        $user2->centres()->attach(1, ['homeCentre' => true]);

        // ARC admin is an fmuser in centre 2, which has individual forms on the dashboard.
        $user3 = factory(CentreUser::class)->state('FMUser')->create([
            "name"  => "ARC fmuser2",
            "email" => "arc+fmuser2@neontribe.co.uk",
            "password" => bcrypt('store_pass'),
        ]);
        $user3->centres()->attach(2, ['homeCentre' => true]);

        // 4 faked users associated with random Centres
        factory(CentreUser::class, 4)
            ->create()
            ->each(function ($centreUser) {
                $centres  = Centre::get();
                if ($centres->count() > 0) {
                    // Pick a random Centre
                    $centre = $centres[random_int(0, $centres->count()-1)];
                } else {
                    // There should be at least one Centre
                    $centre = factory(Centre::class)->create();
                }
                $centreUser->centres()->attach($centre->id, ['homeCentre' => true]);
            });

        // 2 deleted users
        $deletedUsers = factory(CentreUser::class, 2)->create([
            "deleted_at"  => date("Y-m-d H:i:s"),
        ]);

        // an SP user for testing
        $socialPrescriber = factory(CentreUser::class)->create([
            'name' => 'prescribing user',
            'email' => 'arc+spuser@neontribe.co.uk',
            "password" => bcrypt('store_pass'),
        ]);
        $spcId = Centre::where('name', 'Prescribing Centre')->first()->id;
        $socialPrescriber->centres()->attach($spcId, ["homeCentre" => true]);

        // Scottish user for testing
        $scottishUser = factory(CentreUser::class)->create([
            'name' => 'Scottish user',
            'email' => 'arc+scuser@neontribe.co.uk',
            'password' => bcrypt('store_pass'),
        ]);
        $scottishUser->centres()->attach(8, ['homeCentre' => true]);

        // Southwark user for testing
        $southwarkUser = factory(CentreUser::class)->create([
            'name' => 'Southwark user',
            'email' => 'arc+swuser@neontribe.co.uk',
            'password' => bcrypt('store_pass'),
        ]);
        $southwarkUser->centres()->attach(6, ['homeCentre' => true]);

		// Tower Hamlet SP user for testing
		$towerHamletUser = factory(CentreUser::class)->create([
			'name' => 'Tower Hamlet SP user',
			'email' => 'arc+thuser@neontribe.co.uk',
			'password' => bcrypt('store_pass'),
		]);
		$towerHamletUser->centres()->attach(10, ['homeCentre' => true]);

		// Lambeth SP user for testing
		$lambethUser = factory(CentreUser::class)->create([
			'name' => 'Lambeth SP user',
			'email' => 'arc+lambethuser@neontribe.co.uk',
			'password' => bcrypt('store_pass'),
		]);
		$lambethUser->centres()->attach(11, ['homeCentre' => true]);
    }
}
