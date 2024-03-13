<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tests\CreatesApplication;
use function Termwind\render;

class HandlerTest extends TestCase
{
    use CreatesApplication;


    public function testReport()
    {
        $response = new Response();

        // test ! production - if we are not in production, no special action is taken
        $rte = new RuntimeException();
        $handler = new Handler($this->app);
        $handler->report($rte);

        // test is production
        config(['app.env' => 'production']);
        $app = $this->createApplication();
        $hre = new HttpResponseException($response);
        config(['app.env' => 'production']);
        \Log::expects("error")->once();
        $handler = new Handler($app);
        $handler->report($hre);
    }

    public function testRender()
    {
        $handler = new Handler($this->app);

        $request = new Request();
        $response = $handler->render($request, new RuntimeException());
        $this->assertInstanceOf(Response::class, $response);

        $response = $handler->render($request, new TokenMismatchException());
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $allowed = [];
        $response = $handler->render($request, new MethodNotAllowedHttpException($allowed));
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
