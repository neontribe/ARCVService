<?php
namespace Tests;

use Config;
use http\Exception;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MysqlStoreTestCase extends StoreTestCase
{
    use DatabaseMigrations;

    private const TESTING_MYSQL_FALLBACK = 'testing-mysql';

    protected function setUp(): void
    {
        parent::setUp();

        // Fallback to the MySQL testing database if the default testing database doesn't use the MySQL driver
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        if ($driver !== 'mysql') {
            $connection = self::TESTING_MYSQL_FALLBACK;
            // Set the default driver
            Config::set('database.default', $connection);
            // Set passport's sneaky auto-included migrations to the same as well
            Config::set('passport.storage.database.connection', $connection);
        }

        // Check we can connect to the database before we test. This test is effectively optional, so it would be rude
        // to just error out
        try {
            $this->runDatabaseMigrations();
        } catch (Exception $exception) {
            $this->markTestSkipped(
                'Raw queries with specific functions need the MySQL database "' . $connection .
                '", but it was unavailable: ' . $exception->getMessage()
            );
        }
    }
}