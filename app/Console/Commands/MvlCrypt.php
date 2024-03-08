<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MvlCrypt extends Command
{
    const TICK_SIZE = 1000;

    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:mvl:encrypt {file : file to encrypt}';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Encrypt a single file where filename.ext will be written to filename.ext.enc';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $in_file = $this->argument("file");
        if (!file_exists($in_file)) {
            $this->error(sprintf("File not found: %s", $in_file));
        }

        // Add encryption wrapper
        if (!in_array("ssw", stream_get_wrappers())) {
            stream_wrapper_register("ssw", "App\Wrappers\SecretStreamWrapper");
        }

        $targetDir = dirname($in_file);
        $sswTargetDir = "ssw://" . $targetDir;

        $out_file = $sswTargetDir . "/" . basename($in_file) . ".enc";
        $this->info(sprintf("Encrypting %s to %s", $in_file, $out_file));

        $fh_out = fopen($out_file, 'w');
        $fh_in = fopen($in_file, "r");
        $iterator = $this->yeildyFileReader($fh_in);

        foreach ($iterator as $iteration) {
            fputs($fh_out, $iteration);
        }

        fclose($fh_in);
        fclose($fh_out);

        stream_wrapper_unregister("ssw");

        return 0;
    }

    private function yeildyFileReader($handle)
    {
        while (!feof($handle)) {
            yield trim(fgets($handle));
        }
    }
}
