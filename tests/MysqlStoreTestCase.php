<?php

namespace Tests;

use Exception;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

abstract class MysqlStoreTestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseMigrations;

    private const TESTING_MYSQL_FALLBACK = 'testing-mysql';

    private ?string $originalConnection = null;

    protected function setUp(): void
    {
        // Skip before booting the app — no cleanup needed at all
        if (env('PHPUNIT_SKIP_MYSQL_TEST', false)) {
            $this->markTestSkipped('Skipped - PHPUNIT_SKIP_MYSQL_TEST is set.');
        }

        parent::setUp();

        // Check the fallback connection is actually configured with a driver
        $fallbackConfig = config('database.connections.' . self::TESTING_MYSQL_FALLBACK);
        if (empty($fallbackConfig['driver'])) {
            $this->markTestSkipped(
                'Skipped - testing-mysql connection is not configured.'
            );
        }

        // Only mutate config if we actually need to switch
        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        if ($driver !== 'mysql') {
            $this->originalConnection = $connection;
            config(['database.default' => self::TESTING_MYSQL_FALLBACK]);
            config(['passport.storage.database.connection' => self::TESTING_MYSQL_FALLBACK]);
        }

        // Try to migrate — skip gracefully if MySQL is unreachable
        try {
            $this->runDatabaseMigrations();
        } catch (Exception $e) {
            $this->restoreConnection();
            $this->markTestSkipped(
                'Skipped - MySQL unavailable: ' . $e->getMessage()
            );
        }
    }

    protected function tearDown(): void
    {
        $this->restoreConnection();
        parent::tearDown();
    }

    private function restoreConnection(): void
    {
        if ($this->originalConnection !== null) {
            config(['database.default' => $this->originalConnection]);
            config(['passport.storage.database.connection' => $this->originalConnection]);
            $this->originalConnection = null;
        }
    }

    /**
     * @param $selector string Selector string to find a bunch of elements
     * @param $text string String you're looking for
     * @param $pos int Position in the returned element array you think the text will be.
     * @return $this
     */
    public function seeInElementAtPos(string $selector, string $text, int $pos): static
    {
        $element_text = trim($this->crawler->filter($selector)->eq($pos)->text());
        $this->assertStringContainsString($text, $element_text);
        return $this;
    }
}
