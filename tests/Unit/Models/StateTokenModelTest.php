<?php

namespace Tests\Unit\Models;

use App\StateToken;
use Illuminate\Database\QueryException;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class StateTokenModelTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function testItCanGenerateAPaymentUUID()
    {
        // Create a StateToken
        $token = factory(StateToken::class)->create();

        // Check it has a UUID that is valid;
        $this->assertTrue(Uuid::isValid($token->uuid));

        // Generate a new token with a specified value
        $uuid = StateToken::generateUnusedToken();
        $specifiedToken = factory(StateToken::class)->make(['uuid' => $uuid]);
        $specifiedToken->save();

        // See that saved correctly
        $specifiedToken->fresh();
        $this->assertEquals($uuid, $specifiedToken->uuid);
    }

    /** @test */
    public function testItCannotSaveADuplicateUUID()
    {
        $this->expectExceptionMessage("Integrity constraint violation: 19 UNIQUE constraint failed: state_tokens.uuid");
        $this->expectException(QueryException::class);
        // Create a StateToken, should make a random, unique uuid
        $token = factory(StateToken::class)->create();

        // Try to create a new token with the same value
        // This will throw the above QueryException with the message substring specified
        $token2 = factory(StateToken::class)->create(['uuid' => $token->uuid]);

        $this->assertFalse($token2);
    }
}
