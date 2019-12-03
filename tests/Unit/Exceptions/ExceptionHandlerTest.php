<?php

namespace Tests\Unit\Controllers\Store;

use App\Exceptions\Handler;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Config;
use League\OAuth2\Server\Exception\OAuthServerException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tests\StoreTestCase;

class ExceptionHandlerTest extends StoreTestCase
{

    /** @test  */
    public function itShouldNotStackTracesInProduction()
    {
        // Set the exceptions to test
        $exceptions = [
            new OAuthServerException('', 4, "exception"),
        ];

        // get our current config.
        $old_config = config('app.env');

        // Get the Handler instance.
        $handler = Container::getInstance()->make(Handler::class);

        try {
            foreach ($exceptions as $exception) {
                Config::set('app.env', 'production');
                $this->assertFalse(
                    $this->invokeMethod($handler, 'shallWeStackTrace', [$exception])
                );

                // This shouldn't run in production, but just to check...
                if ($old_config !== 'production') {
                    Config::set('app.env', $old_config);
                    $this->assertTrue(
                        $this->invokeMethod($handler, 'shallWeStackTrace', [$exception])
                    );
                }
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
     * @throws ReflectionException
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
