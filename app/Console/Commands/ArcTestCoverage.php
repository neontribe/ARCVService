<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArcTestCoverage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:test:coverage {file : path to XML coverage report} { acceptance=65 : minimum % to pass }';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Reads in a report created by `phpunit --coverage-xml` and parses it. Fails if the percentage falls below the acceptance level.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument("file");
        $acceptance = (double) $this->argument("acceptance");


        $coverage = simplexml_load_file($file);
        $ratio = (double)$coverage->project->directory->totals->lines["percent"];

        $this->info("Line coverage: $ratio%");
        $this->info("Threshold: $acceptance%");

        if ($ratio < $acceptance) {
            $this->error("FAILED!");
            return -1;
        }

        $this->info("SUCCESS!");
        return 0;
    }
}
