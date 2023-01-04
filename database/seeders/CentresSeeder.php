<?php
namespace Database\Seeders;

use App\Centre;
use Illuminate\Database\Seeder;

class CentresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // fix sponsor 1.
        factory(Centre::class)->create(["sponsor_id" => 1]);

        //random centres
        $centres = factory(Centre::class, 3)->create();

        // Grab one and change print pref to individual and another to collection.
        $centres[0]->print_pref = 'collection';
        $centres[0]->save();
        $centres[1]->print_pref = 'individual';
        $centres[1]->save();

        // 3 centres attached to sponsors with IDs to mirror live data
        factory(Centre::class)->create(["sponsor_id" => 3]);
        factory(Centre::class)->create(["sponsor_id" => 4]);
        factory(Centre::class)->create(["sponsor_id" => 6]);

        // Scottish centre
        factory(Centre::class)->create(["sponsor_id" => 8]);

        // Social prescribing centre
        factory(Centre::class)->create(['name' => 'Prescribing Centre', 'sponsor_id' => 9]);
    }
}
