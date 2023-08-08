<?php

namespace Tests\Unit\Providers;

use App\Providers\MandrillMailProvider;
use Illuminate\Foundation\Application;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Bridge\Mailchimp\Transport\MandrillApiTransport;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tests\TestCase;
use Exception;

/**
 * required to keep overloaded mocks out of other tests
 * Note, this screws with xdebug, which loses track of processes
 * Disable these to debug individual tests in this file
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MandrillMailServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mail.driver', 'mandrill');
        Config::set('mail.default', 'mandrill');
        Config::set('mail.mailers.mandrill.transport', 'mandrill');
        Config::set('services.mandrill.key', 'SomeRandomString');
    }

    /** @test */
    public function it_extends_the_mail_manager_with_mandrill_driver(): void
    {
        $driver = Mail::driver('mandrill');
        $this->assertInstanceOf(Mailer::class, $driver);
    }

    /** @test */
    public function it_might_try_to_hand_off_to_mandrill(): void
    {
        // mock the CurlHttpClient the symfony uses to _avoid_ actually contacting mandrill
        $mockHttpClient = Mockery::mock(
            'overload:Symfony\Component\HttpClient\CurlHttpClient',
            'Symfony\Contracts\HttpClient\HttpClientInterface'
        );

        // When it's asked to make a request, give back something harmless but will throw a connection error.
        $mockHttpClient->shouldReceive('request')
            ->andReturnUsing(
                function (): ResponseInterface {
                    return new MockResponse(json_encode([['_id' => 'test']]));
                }
            );

        try {
            // make a raw email
            Mail::raw('Hello, welcome to Laravel!', static function ($message) {
                $message
                    ->to('test@example.com')
                    ->subject('test email');
            });
        } catch (Exception $e) {
            $this->assertInstanceOf(HttpTransportException::class, $e);
            // did our mock prevent access to mandrill?
            $this->assertStringContainsString('Could not reach the remote Mandrill server.', $e->getMessage());
            // did mandrill api client report this?
            $this->assertStringContainsString(MandrillApiTransport::class, $e->getTraceAsString());
        }
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders(Application $app): array
    {
        return [MandrillMailProvider::class];
    }
}
