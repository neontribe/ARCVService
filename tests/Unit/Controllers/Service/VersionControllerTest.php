<?php

namespace Tests\Unit\Controllers\Service;

use Tests\TestCase;

class VersionControllerTest extends TestCase
{
    public function testVersion()
    {
        $response = $this->get(route('version'))->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey("Service/API", $json);
    }
}
