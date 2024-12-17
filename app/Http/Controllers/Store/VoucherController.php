<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Services\TextFormatter;
use App\Wrappers\SecretStreamWrapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use URL;

class VoucherController extends Controller
{
    // This belongs here because it's largely about arranging vouchers
    public function exportMasterVoucherLog()
    {
        // Set up the Storage dir.
        $disk = Storage::disk(config('arc.mvl_disk'));
        $archiveName = config('arc.mvl_filename');

        // Check the log exists, so we can error-out before we declare a streamed response.
        if (!$disk->exists($archiveName)) {
            // Return to dashboard with an error that indicates we don't have a file.
            // TODO : This error copy needs some UI.
            Log::error("File does not exist: " . $archiveName);
            return redirect(URL::route('store.dashboard'))
                ->with('error_message', "Sorry, couldn't find a current export. Please check the exporter ran on schedule");
        }

        // Inject the original file last_modified into the d/l file name.
        $filename = pathinfo($archiveName, PATHINFO_BASENAME) .
            "_" . strftime('%Y-%m-%d_%H%M', $disk->lastModified($archiveName)) .
            "." . pathinfo($archiveName, PATHINFO_EXTENSION);

        // TODO : refactor SecretStreamWrapper to handle reads, decoupling this controller from crypto code

        // Do as much IO as we can comfortably before we begin streaming.
        $file = fopen($disk->path($archiveName), 'r');
        $header = fread($file, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);

        try {
            // Initialise the decryption stream with its initial header. For more documentation, SecretStreamWrapper.
            $stream = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, SecretStreamWrapper::getKey());
        } catch (\SodiumException $e) {
            // TODO : This error copy needs some UI.
            return redirect(URL::route('store.dashboard'))
                ->with('error_message', "Sorry, the export file was unreadable. Please contact support.");
        }

        // We want to only keep a small buffer in memory at any time, so our response will be streamed.
        return new StreamedResponse(function () use ($file, &$stream) {
            do {
                // Read the next message.
                $part = fread($file, SecretStreamWrapper::MESSAGE_SIZE + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);

                if ($part === false) {
                    // We couldn't read from the file. Log that as an error.
                    Log::error("IO error when reading log");
                    throw new \RuntimeException("IO error when reading log");
                }

                // Decrypt the message.
                $part = sodium_crypto_secretstream_xchacha20poly1305_pull($stream, $part);

                if ($part === false) {
                    // Decryption failed. Log that as an error.
                    Log::error("Decryption error when reading log");
                    throw new \RuntimeException("Decryption error when reading log");
                }

                // Split the decrypted message into content and metadata.
                list($message, $tag) = $part;

                // The last message should be tagged as such, to ensure there was no tampering after encryption.
                $eof = feof($file);
                $lastMessage = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
                if ($eof != $lastMessage) {
                    // We met the end of the file before the last message or vice-versa. Log that error.
                    Log::error("Log read ended prematurely");
                    throw new \RuntimeException("Log read ended prematurely");
                }

                // Stream the decrypted content.
                echo $message;
            } while (!$eof); // While there is more to do, continue.
        }, 200, [
            'Content-Type' => 'application/x-zip',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Expires' => Carbon::createFromTimestamp(0)->format('D, d M Y H:i:s'),
            'Last-Modified' => Carbon::now()->format('D, d M Y H:i:s'),
            'Cache-Control' => 'private, no-cache',
            'Pragma' => 'no-cache',
            // TODO : Estimate zip size for a progress bar on what is quite a large file
        ]);
    }

    public function listVoucherLogs()
    {
        // by gabriel (intern 4)

        $directoryPath = Storage::path('enc');

        $logFiles = File::glob($directoryPath . '/*.arcx.csv');
        rsort($logFiles);
        $logFilesMetadata = [];
        foreach ($logFiles as $logFile) {
            $logFilesMetadata[$logFile] = "xxx";
        }

        $data = [];
        foreach ($logFilesMetadata as $fileName => $metadata) {
            $data[$this->makeDisplayName($fileName)] = [
                "displayDate" => date("d/m/Y H:s", filemtime($fileName)),
                "fileSize" => TextFormatter::formatBytes(filesize($fileName)),
                "downloadLink" => "/vouchers/download?logFile=" . $fileName,
            ];
        }

        return view('store.list_voucher_logs', ["data" => $data]);
    }

    public function downloadAndDecryptVoucherLogs(Request $request)
    {
        $logFile = $request->query('logFile');
        // Is there a way for people to maliciously pass bad queries?
        // I don't think they can do this, since it checks only searches within storage/app/local.
        // The website will crash though if the file isn't encrypted properly/at all.

        $pathToVouchers = Storage::path('enc') . '/' . $logFile;

        // Check the log exists, so we can error-out before we declare a streamed response.
        if (!file_exists($pathToVouchers)) {
            // Return to dashboard with an error that indicates we don't have a file.
            return redirect(URL::route('store.dashboard'))
                ->with('error_message', "Sorry, we couldn't find the file you were looking for. Please contact support if this error persists.");
        }

        // Do as much IO as we can comfortably before we begin streaming.
        $file = fopen($pathToVouchers, 'r');

        $header = fread($file, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);

        try {
            // Initialise the decryption stream with its initial header. For more documentation, SecretStreamWrapper.
            $stream = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, SecretStreamWrapper::getKey());
        } catch (\SodiumException $e) {
            // TODO : This error copy needs some UI.
            return redirect(URL::route('store.dashboard'))
                ->with('error_message', "Sorry, the export file was unreadable. Please contact support.");
        }

        # slightly (very) reused
        return new StreamedResponse(function () use ($file, &$stream) {
            do {
                // Read the next message.
                $part = fread($file, SecretStreamWrapper::MESSAGE_SIZE + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES);

                if ($part === false) {
                    // We couldn't read from the file. Log that as an error.
                    Log::error("IO error when reading log");
                    throw new \RuntimeException("IO error when reading log");
                }

                // Decrypt the message.
                $part = sodium_crypto_secretstream_xchacha20poly1305_pull($stream, $part);

                if ($part === false) {
                    // Decryption failed. Log that as an error.
                    Log::error("Decryption error when reading log");
                    throw new \RuntimeException("Decryption error when reading log");
                }

                // Split the decrypted message into content and metadata.
                list($message, $tag) = $part;

                // The last message should be tagged as such, to ensure there was no tampering after encryption.
                $eof = feof($file);
                $lastMessage = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
                if ($eof != $lastMessage) {
                    // We met the end of the file before the last message or vice-versa. Log that error.
                    Log::error("Log read ended prematurely");
                    throw new \RuntimeException("Log read ended prematurely");
                }

                // Stream the decrypted content.
                echo $message;
            } while (!$eof); // While there is more to do, continue.
        }, 200, [
            'Content-Disposition' => 'attachment; filename="' . $logFile . '"'
        ]);

    }

    private function makeDisplayName($filename)
    {
        $basename = basename($filename);
        $start = date_parse_from_format(
            "Ymd",
            substr($basename, 9, 8)
        );
        $end = date_parse_from_format(
            "Ymd",
            substr($basename, 21, 8)
        );

        return sprintf(
            "%02d/%02d/%d to %02d/%02d/%d",
            $start["day"],
            $start["month"],
            $start["year"],
            $end["day"],
            $end["month"],
            $end["year"],
        );
    }
}
