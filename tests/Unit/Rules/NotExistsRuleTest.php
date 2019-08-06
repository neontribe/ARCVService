<?php

namespace Tests\Unit\Rules;

use App\User;
use App\Rules\NotExistsRule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NotExistsRuleTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function theNotExistsRuleValidates()
    {
        // Create a rules set.
        $rule = [
            'user_id' => [
                // Use a table we know exists.
                new NotExistsRule('users', 'id')
            ]
        ];

        // Make a user;
        $user = factory(User::class)->create();

        // Succeed at failing to find a user id
        $this->assertTrue(validator(['user_id' => $user->id +1 ], $rule)->passes());

        // Fail at failing to find a user id
        $this->assertFalse(validator(['user_id' => $user->id], $rule)->passes());
    }
}