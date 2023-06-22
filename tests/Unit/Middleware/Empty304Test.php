<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\Empty304;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class Empty304Test extends TestCase
{
    /** @test */
    public function testContentIsCleared(): void
    {
        $request = new Request();

        $empty304middleware = new Empty304();
        $response = $empty304middleware->handle($request, function () {
            $r = new Response();
            $r->setStatusCode(301);
            $r->setContent("Whether I shall turn out to be the hero of my own life, or whether that station will be held by anybody else, these pages must show.");
            return $r;
        });

        $this->assertEmpty($response->getContent());
    }
}