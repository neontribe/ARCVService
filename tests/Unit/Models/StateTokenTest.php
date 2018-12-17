<?php

namespace Tests\Unit\Models;

use App\StateToken;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class StateTokenModelTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function testItCanGenerateAPaymentUUID()
    {
        // Create a StateToken
        $token = new StateToken();
        // Check it has a UUID that is valid;
        $this->assertTrue(Uuid::isValid($token->uuid));

        // Generate a new token with a specified value
        $uuid = $token->generateUnusedToken();
        $specifiedToken = new StateToken(['uuid' => $uuid]);
        $specifiedToken->save();

        // See that saved correctly
        $specifiedToken->fresh();
        $this->assertEquals($uuid, $specifiedToken->uuid);
    }

    /** @test */
    public function testItCanCheckTheUUIDisUnique()
    {
        // Create a StateToken
        $token = new StateToken();
        $token->save();
        // Try to create a new token with the same value
        $token2 = new StateToken(['uuid' => $token->uuid]);
        // See that it is different
        $this->assertNotEquals($token->uuid, $token2->uuid);
    }
}
