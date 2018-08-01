<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SearchPageFuzzyTest extends TestCase
{
    use DatabaseMigrations;

    private $default_db = null;

    public function setUp()
    {
        parent::setUp();

        // get the current DB driver.
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        // SQLlite doesn't understand the "IF" function that Searchy uses.
        if ($driver != "mysql") {
            $this->markTestSkipped(
                "Fuzzy searching with TomLingham/Laravel-Searchy needs the mysql driver due to complex conditionals."
            );
        }
        //$this->default_db = env("DB_CONNECTION");
        //putenv("DB_CONNECTION=mysql_testing");
    }

    public function tearDown()
    {
        parent::tearDown();
        //putenv("DB_CONNECTION=". $this->default_db);
    }

    /** @test */
    public function iCanSearchForExactMatches()
    {
    }

    /** @test */
    public function iCanSearchForStartOfStringMatches()
    {
    }

    /** @test */
    public function iCanSearchForAcronymMatches()
    {
    }

    /** @test */
    public function iCanSearchForConsecutiveMatches()
    {
    }

    /** @test */
    public function iCanSearchForStarOfWordMarches()
    {
    }

    /** @test */
    public function iCanSearchForStudlyCaseMatches()
    {
    }

    /** @test */
    public function iCanSearchForSubtringMatches()
    {
    }

}
