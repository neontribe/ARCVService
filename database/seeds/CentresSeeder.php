<?php

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
        //random centres
        $centres = factory(App\Centre::class, 4)->create();

        // Grab one and change print pref to individual and another to collection.
        $centres[0]->print_pref = 'collection';
        $centres[0]->save();
        $centres[1]->print_pref = 'individual';
        $centres[1]->save();

        // 3 centres attached to sponsors with IDs to mirror live data
        factory(App\Centre::class)->create(["sponsor_id" => 3]);
        factory(App\Centre::class)->create(["sponsor_id" => 4]);
        factory(App\Centre::class)->create(["sponsor_id" => 6]);
    }
}
