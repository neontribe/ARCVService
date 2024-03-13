<?php

namespace Tests\Unit\Controllers\Service\Auth;

use App\Http\Controllers\Service\Auth\ForgotPasswordController;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\View\View;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
{

    public function testBroker()
    {
        $fpc = new ForgotPasswordController();
        $reflection = new \ReflectionClass($fpc);
        $method = $reflection->getMethod("broker");
        $method->setAccessible(true);
        $result = $method->invoke($fpc);
        $this->assertInstanceOf(PasswordBroker::class, $result);
    }
    public function testShowLinkRequestForm()
    {
        $fpc = new ForgotPasswordController();
        $result = $fpc->showLinkRequestForm();
        $this->assertInstanceOf(View::class, $result);
    }
}
