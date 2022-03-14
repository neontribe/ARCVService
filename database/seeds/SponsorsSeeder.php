<?php

use App\Evaluation;
use Illuminate\Database\Seeder;
use PHPMD\RuleSet;

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

        // Gets the SK rules (OLD SK SPONSOR RULES)
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


        // NEW SK SPONSPOR RuleSet
        $sponsor6 = App\Sponsor::find(6);
        $sponsor6->name = "Southwark";
        $sponsor6->save();
        $sponsor6->evaluations()->saveMany($this->southwarkFamilyOverrides());
        $sponsor6->evaluations()->saveMany($this->veryfiesKids());

        // 4MAY20-VC1-CH1P-HA-012020 is in sponsor 5, unmodified
        // 1MAY20a-VC2-CH1-HI-042019 is in sponsor 5, unmodified

        $noTapSponsor = factory(App\Sponsor::class)->create(['name' => "No Tap Project", "can_tap" => false]);
        $noTapSponsor->evaluations()->saveMany($this->qualifyPrimarySchoolers());
        $noTapSponsor->evaluations()->saveMany($this->veryfiesKids());

        // Create a Sponser that will have the Scottish evaluations applied
        // $scottishRulesSponser = factory(App\Sponsor::class)->create(['name' => "Scottish Rules Project", 'is_scotland' => 1]);
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
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "FamilyIsPregnant",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => "4",
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // Turn off ChildIsPrimarySchoolAge rule
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
            // Needs a different check than England
            new Evaluation([
                    "name" => "ScottishChildIsAlmostPrimarySchoolAge",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            // Get rid of this rule
            new Evaluation([
                    "name" => "ChildIsAlmostPrimarySchoolAge",
                    "value" => NULL,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            // New rule for Scotland
            new Evaluation([
                    "name" => "ScottishChildCanDefer",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
        ];
    }

    public function southwarkFamilyOverrides()
    {
        return [
            new Evaluation([
                "name" => "FamilyIsPregnant",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "ChildIsAlmostSecondarySchoolAge",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Family",
            ]),
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => "4",
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsSecondarySchoolAge",
                "value" => 0,
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
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
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
