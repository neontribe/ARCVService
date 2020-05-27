<?php

use App\Evaluation;
use Illuminate\Database\Seeder;

class SponsorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //create RVNT sponsor for tests
        $sponsor = factory(App\Sponsor::class)->create(['name' => "Real Virtual Project", "shortcode" =>"RVNT"]);

        $noticesUnverifiedKids = new Evaluation([
            "name" => "FamilyHasUnverifiedChildren",
            "value" => 0,
            "purpose" => "notices",
            "entity" => "App\Family",
            "sponsor_id" => $sponsor->id,
        ]);
        $noticesUnverifiedKids->save();

        $sponsor->evaluations()->saveMany($this->qualifyPrimarySchoolers());
        $sponsor->evaluations()->saveMany($this->veryfiesKids());

        // And 5 default factory models to be able to mirror live data
        factory(App\Sponsor::class, 5)->create();

        // Gets the SK rules
        $sponsor2 = App\Sponsor::find(2);
        $sponsor2->evaluations()->saveMany($this->qualifyPrimarySchoolers());
        $sponsor2->evaluations()->saveMany($this->veryfiesKids());


        // Gets SK rules, without the primary school disqualification
        $sponsor3 = App\Sponsor::find(3);
        $sponsor3->evaluations()->saveMany($this->allowPrimarySchoolers());
        $sponsor3->evaluations()->saveMany($this->veryfiesKids());

        // only gets verifications
        $sponsor4 = App\Sponsor::find(4);
        $sponsor4->evaluations()->saveMany($this->veryfiesKids());
    }

    public function veryfiesKids()
    {
        return [
            new Evaluation([
                "name" => "FamilyHasNoEligibleChildren",
                "value" => 0,
                "purpose" => "disqualifiers",
                "entity" => "App\Family",
            ])
        ];
    }

    public function allowPrimarySchoolers()
    {
        return [
            // warn when primary schoolers are approaching end of school
            new Evaluation([
                "name" => "ChildIsAlmostSecondarySchoolAge",
                "value" => "0",
                "purpose" => "notices",
                "entity" => "App\Child",
            ]),
            // credit primary schoolers
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => "3",
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // don't disqualify primary schoolers
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ]),
            // do secondary schoolers instead
            new Evaluation([
                "name" => "ChildIsSecondarySchoolAge",
                "value" => 0,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ])
        ];
    }

    public function qualifyPrimarySchoolers()
    {
        $primarySchoolers = $this->allowPrimarySchoolers();
        $primarySchoolers[] = new Evaluation([
                "name" => "FamilyHasNoEligibleChildren",
                "value" => 0,
                "purpose" => "disqualifiers",
                "entity" => "App\Family",
            ]);
        return $primarySchoolers;
    }
}
