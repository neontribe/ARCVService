<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use League\OAuth2\Server\Exception\OAuthServerException;
use ReflectionClass;
use ReflectionException;
use Tests\StoreTestCase;

class ExceptionHandlerTest extends StoreTestCase
{
    private $exceptions = [];

    protected function setUp(): void
    {
        // Tests reach into the container, and get upset when run in bulk with other tests.
        $this->createApplication();
        $this->exceptions = [
            new OAuthServerException('mymessage', 4, "exception"),
        ];
    }


    public function testItShouldNotStackTraceInProduction(): void
    {
        $handler = Container::getInstance()->make(Handler::class);
        try {
            foreach ($this->exceptions as $exception) {
                Config::set('app.env', 'production');
                $this->assertFalse(
                    $this->invokeMethod($handler, 'shallWeStackTrace', [$exception])
                );
            }
        } catch (ReflectionException $e) {
            // Just catch it.
        }
    }


    public function testItShouldStackTraceNotInProduction(): void
    {
        $handler = Container::getInstance()->make(Handler::class);
        try {
            foreach ($this->exceptions as $exception) {
                // hashed to be "anything other than 'production'
                Config::set('app.env', md5('production'));
                $this->assertTrue(
                    $this->invokeMethod($handler, 'shallWeStackTrace', [$exception])
                );
            }
        } catch (ReflectionException $e) {
            // Just catch it.
        }
    }

    /**
     * Call protected/private method of a class using Reflection
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     * @return mixed Method return.
     * @throws ReflectionException
     */
    public function invokeMethod(object &$object, string $methodName, array $parameters = array()): mixed
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
