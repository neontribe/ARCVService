<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MvlBundle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:mvl:bundle';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Bundle processed MVL files into a set of files ready for import.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
//            $filename = sprintf(
//                "%s/mvl/export/vouchers.%s.%04d.csv",
//                Storage::path(self::DISK),
//                $today,
//                floor(($offset + 1) / $this->chunkSize)
//            );
    }
}
