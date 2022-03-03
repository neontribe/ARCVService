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
        // create RVNT sponsor for tests
        // also used by 3MAY20-VC1-CH6-HAS-112019
        $sponsor = factory(App\Sponsor::class)->create(['name' => "Real Virtual Project", "shortcode" =>"RVNT"]);

        $sponsor->evaluations()->saveMany($this->qualifyPrimarySchoolers());
        $sponsor->evaluations()->saveMany($this->veryfiesKids());

        // And 5 default factory models to be able to mirror live data
        factory(App\Sponsor::class, 5)->create();

        // Gets the SK rules
        // used by 2MAY20-VC1-CH2-HI-122018
        $sponsor2 = App\Sponsor::find(2);
        $sponsor2->evaluations()->saveMany($this->qualifyPrimarySchoolers());
        $sponsor2->evaluations()->saveMany($this->veryfiesKids());


        // Gets SK rules, without the primary school disqualification
        // used by 5MAY20-VC1-CH3-HA-112015
        $sponsor3 = App\Sponsor::find(3);
        $sponsor3->evaluations()->saveMany($this->allowPrimarySchoolers());
        $sponsor3->evaluations()->saveMany($this->veryfiesKids());

        // only gets verifications
        // used by 6MAY-20-VC1-CH2-032020
        $sponsor4 = App\Sponsor::find(4);
        $sponsor4->evaluations()->saveMany($this->veryfiesKids());

        // 4MAY20-VC1-CH1P-HA-012020 is in sponsor 5, unmodified
        // 1MAY20a-VC2-CH1-HI-042019 is in sponsor 5, unmodified

        $noTapSponsor = factory(App\Sponsor::class)->create(['name' => "No Tap Project", "can_tap" => false]);
        $noTapSponsor->evaluations()->saveMany($this->qualifyPrimarySchoolers());
        $noTapSponsor->evaluations()->saveMany($this->veryfiesKids());


        $scottishRulesSponser = factory(App\Sponsor::class)->create(['name' => "Scottish Rules Project"]);
        $scottishRulesSponser->evaluations()->saveMany($this->scottishFamilyOverrides());
        $scottishRulesSponser->evaluations()->saveMany($this->veryfiesKids());
    }

    public function veryfiesKids()
    {
        return [
            new Evaluation([
                "name" => "FamilyHasUnverifiedChildren",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Family",
            ])
        ];
    }

    public function scottishFamilyOverrides()
    {
        return [
            new Evaluation([
                "name" => "FamilyIsPregnant",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // credit primary schoolers
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => "4",
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
            new Evaluation([
                    "name" => "FamilyHasNoEligibleChildren",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Family",
            ]),
            new Evaluation([
                    "name" => "ScottishChildIsAlmostPrimarySchoolAge",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ChildIsAlmostPrimarySchoolAge",
                    "value" => NULL,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "ScottishChildCanDefer",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
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
