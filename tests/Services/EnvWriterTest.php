<?php

namespace Tests\Services;

use App\Services\EnvWriter;
use PHPUnit\Framework\TestCase;

class EnvWriterTest extends TestCase
{
    private string $envPath;

    // -------------------------------------------------------------------------
    // Setup
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();
        $this->envPath = tempnam(sys_get_temp_dir(), 'envwriter_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeWriter(): EnvWriter
    {
        return new EnvWriter($this->envPath);
    }

    private function writeEnv(string $contents): void
    {
        file_put_contents($this->envPath, $contents);
    }

    private function readEnv(): string
    {
        return file_get_contents($this->envPath);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function testUpdatesMatchingKey(): void
    {
        $this->writeEnv("PASSWORD_CLIENT_SECRET=old-secret\n");

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $this->assertStringContainsString('PASSWORD_CLIENT_SECRET=new-secret', $this->readEnv());
    }

    public function testRemovesOldValueFromFile(): void
    {
        $this->writeEnv("PASSWORD_CLIENT_SECRET=old-secret\n");

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $this->assertStringNotContainsString('old-secret', $this->readEnv());
    }

    public function testLeavesOtherKeysUnchanged(): void
    {
        $this->writeEnv(
            "APP_NAME=MyApp\n" .
            "PASSWORD_CLIENT_SECRET=old-secret\n" .
            "APP_ENV=local\n"
        );

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $env = $this->readEnv();
        $this->assertStringContainsString('APP_NAME=MyApp', $env);
        $this->assertStringContainsString('APP_ENV=local', $env);
    }

    public function testLeavesFileUnchangedWhenKeyNotPresent(): void
    {
        $original = "APP_NAME=MyApp\nAPP_ENV=local\n";
        $this->writeEnv($original);

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $this->assertSame($original, $this->readEnv());
    }

    public function testUpdatesKeyWithEmptyCurrentValue(): void
    {
        $this->writeEnv("PASSWORD_CLIENT_SECRET=\n");

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $this->assertStringContainsString('PASSWORD_CLIENT_SECRET=new-secret', $this->readEnv());
    }

    public function testUpdatesKeyOnFirstLine(): void
    {
        $this->writeEnv("PASSWORD_CLIENT_SECRET=old-secret\nAPP_NAME=MyApp\n");

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $this->assertStringContainsString('PASSWORD_CLIENT_SECRET=new-secret', $this->readEnv());
    }

    public function testUpdatesKeyOnLastLineWithNoTrailingNewline(): void
    {
        $this->writeEnv("APP_NAME=MyApp\nPASSWORD_CLIENT_SECRET=old-secret");

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        $this->assertStringContainsString('PASSWORD_CLIENT_SECRET=new-secret', $this->readEnv());
    }

    public function testDoesNotMatchPartialKeyName(): void
    {
        $this->writeEnv("OTHER_PASSWORD_CLIENT_SECRET=old-secret\n");

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'new-secret');

        // Partial match should not have been updated
        $this->assertStringContainsString('OTHER_PASSWORD_CLIENT_SECRET=old-secret', $this->readEnv());
        $this->assertStringNotContainsString('PASSWORD_CLIENT_SECRET=new-secret', $this->readEnv());
    }

    public function testWritesCorrectlyToRealisticEnvFile(): void
    {
        $this->writeEnv(
            "APP_NAME=ARCVService\n" .
            "APP_ENV=testing\n" .
            "APP_KEY=base64:abc123\n" .
            "DB_CONNECTION=mysql\n" .
            "PASSWORD_CLIENT_SECRET=old-secret\n" .
            "SOME_OTHER_KEY=value\n"
        );

        $this->makeWriter()->updateKey('PASSWORD_CLIENT_SECRET', 'brand-new-secret');

        $env = $this->readEnv();
        $this->assertStringContainsString('PASSWORD_CLIENT_SECRET=brand-new-secret', $env);
        $this->assertStringContainsString('APP_NAME=ARCVService', $env);
        $this->assertStringContainsString('APP_KEY=base64:abc123', $env);
        $this->assertStringContainsString('DB_CONNECTION=mysql', $env);
        $this->assertStringContainsString('SOME_OTHER_KEY=value', $env);
        $this->assertStringNotContainsString('old-secret', $env);
    }

    public function testTempFileIsCleanedUpBetweenTests(): void
    {
        $this->assertFileExists($this->envPath);
    }
}
