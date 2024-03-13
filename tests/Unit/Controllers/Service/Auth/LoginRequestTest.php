<?php

namespace Tests\Unit\Controllers\Service\Auth;

use App\Http\Controllers\API\Auth\LoginRequest;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    public function testAuthorize()
    {
        $lr = new LoginRequest();
        $this->assertTrue($lr->authorize());
    }

    public function testRules()
    {
        $lr = new LoginRequest();
        $rules = $lr->rules();
        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertEquals("required|email", $rules["username"]);
        $this->assertEquals("required", $rules["password"]);
    }
}
