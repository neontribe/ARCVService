<?php

namespace App\Console\Commands;

use App\Wrappers\SecretStreamWrapper;
use Illuminate\Console\Command;

class MvlCat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string $signature
     */
    protected $signature = 'arc:mvl:cat {file : MVL log file decrypted to std out}';
    /**
     * The console command description.
     *
     * @var string $description
     */
    protected $description = 'Decrypts an MVL log file and dumps to std out. This can be used to decrypt any ".arcx" file.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // This decryption is based on ExportMasterVoucherLog() @ app/Http/Controllers/Store/VoucherController.php.
        // Since this has been used a lot of times, maybe a generalised version should be made a function somewhere?

        $in_file = $this->argument("file");

        $this->info(sprintf("Reading logs from %s", $in_file));
        if (!file_exists($in_file)) {
            $this->error(sprintf("Log file not found: %s", $in_file));
        }

        // Opens any given file from root folder - allows any encrypted file using SecretStreamWrapper to be decrypted.
        // Might not be an issue, given that if a bad actor has access to ./artisan to run this we have bigger problems :)
        $file = fopen($in_file, 'r');

        // Get header for SSW.
        $header = fread($file, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);


        try {
            // Initialise the decryption stream with its initial header. For more documentation, SecretStreamWrapper.
            $stream = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, SecretStreamWrapper::getKey());
        } catch (\SodiumException $e) {
            $this->error("Decryption stream could not be initialised.");
        }

        do {
            // Read the next message.
            $part = fread($file, SecretStreamWrapper::MESSAGE_SIZE + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);

            if ($part === false) {
                // We couldn't read from the file. Log that as an error.
                $this->error("IO error when reading log");
            }

            // Decrypt the message.
            $part = sodium_crypto_secretstream_xchacha20poly1305_pull($stream, $part);

            if ($part === false) {
                // Decryption failed. Log that as an error.
                $this->error("Decryption error when reading log");
            }

            // Split the decrypted message into content and metadata.
            list($message, $tag) = $part;

            // The last message should be tagged as such, to ensure there was no tampering after encryption.
            $eof = feof($file);
            $lastMessage = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            if ($eof != $lastMessage) {
                // We met the end of the file before the last message or vice-versa. Log that error.
                $this->error("Log read ended prematurely");
            }

            // Stream the decrypted content.
            echo $message;
        } while (!$eof); // While there is more to do, continue.

    }
}
