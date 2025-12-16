<?php

return [
    'overrides' => [
        /**
         * Scottish variations
         */
        'scottish-family' => [
            // Scotland has 4 not 3
            [
                "name" => "FamilyIsPregnant",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Family",
            ],
            // Scotland has 4 not 3
            [
                "name" => "ScottishChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ],
            [
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Child",
            ],
            // Scotland has 4 not 3
            [
                "name" => "ScottishChildIsPrimarySchoolAge",
                "value" => "4",
                "purpose" => "credits",
                "entity" => "App\Child",
            ],
            // Turn off ChildIsPrimarySchoolAge rule
            [
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ],
            [
                "name" => "ScottishFamilyHasNoEligibleChildren",
                "value" => 0,
                "purpose" => "disqualifiers",
                "entity" => "App\Family",
            ],
            [
                "name" => "FamilyHasNoEligibleChildren",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Family",
            ],
            // Needs a different check than England
            [
                "name" => "ScottishChildIsAlmostPrimarySchoolAge",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Child",
            ],
            // Get rid of this rule
            [
                "name" => "ChildIsAlmostPrimarySchoolAge",
                "value" => null,
                "purpose" => "notices",
                "entity" => "App\Child",
            ],
            // New rule for Scotland
            [
                "name" => "ScottishChildCanDefer",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Child",
            ],
            [
                "name" => "FamilyHasUnverifiedChildren",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Family",
            ],
            [
                "name" => "ChildIsSecondarySchoolAge",
                "value" => 0,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ],
        ],

        /**
         * Social Prescribing overrides
         */
        'social-prescribing' => [
            [
                "name" => "FamilyIsPregnant",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Family",
            ],
            [
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Child",
            ],
            [
                "name" => "ChildIsUnderOne",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Child",
            ],
            [
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ],
            [
                "name" => "DeductFromCarer",
                "value" => -7,
                "purpose" => "credits",
                "entity" => "App\Family",
            ],
            [
                "name" => "HouseholdMember",
                "value" => 7,
                "purpose" => "credits",
                "entity" => "App\Child",
            ],
            [
                "name" => "HouseholdExists",
                "value" => 10,
                "purpose" => "credits",
                "entity" => "App\Family",
            ],
            [
                "name" => "ChildIsAlmostPrimarySchoolAge",
                "value" => null,
                "purpose" => "notices",
                "entity" => "App\Child",
            ],
            [
                "name" => "ChildIsAlmostOne",
                "value" => null,
                "purpose" => "notices",
                "entity" => "App\Child",
            ]
        ]
    ]
];
