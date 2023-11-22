<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Wrappers\SecretStreamWrapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
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

        $directoryPath = storage_path("app/local");

        $logFiles = File::glob($directoryPath . '/*.log');

        $downloadLinks = [];
        $logMetadata = [];
        foreach ($logFiles as $logFile) {
            $baseFileName = basename($logFile);

            $downloadLinks[$baseFileName] = "/vouchers/download?logFile=" . $baseFileName;

            $rawFileSize = filesize($logFile);

            $i = floor(log($rawFileSize) / log(1024));
            $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
            $formattedFileSize = sprintf('%.02F', $rawFileSize / pow(1024, $i)) * 1 . ' ' . $sizes[$i];

            // get metadata
            $logMetadata[$baseFileName] = [
                "fileName" => $baseFileName,
                "rawSize" => filesize($logFile), // to use for sorting
                "formattedSize" => $formattedFileSize, // to use for displaying
                "lastModified" => filemtime($logFile)];
        }

        return view('store.list_voucher_logs', ["downloadLinks" => $downloadLinks, "logMetadata" => $logMetadata]);
    }

    public function downloadVoucherLogs(Request $request)
    {
        $logFile = $request->query('logFile');
        return response()->download(storage_path("app/local/" . $logFile));
    }


    public function testEncryptToDisk()
    {
        // testing how encryption works
        // TODO: Delete this

        $app_key = config("app.key");
        $base64Key = substr($app_key, 7, strlen($app_key));
        $binaryKey = base64_decode($base64Key, true);
        if ($binaryKey === false) {
            throw new Exception("Invalid Base64 key");
        }
        $secretKey = substr(hash('sha256', $binaryKey, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $message = "Hello World!";
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $encryptedMessage = sodium_crypto_secretbox($message, $nonce, $secretKey);

        $filePath = storage_path("app/local/") . "helloworld.enc";

        $file = fopen($filePath, "w");
        //fwrite($file, $encryptedMessage);
        fwrite($file, $nonce.$encryptedMessage);
        fclose($file);
        // return $encryptedMessage;
        return $this->testDecryptFromDisk();

    }

    public function testDecryptFromDisk()
    {
        $filePath = storage_path("app/local/") . "helloworld.enc";
        $app_key = config("app.key");
        $base64Key = substr($app_key, 7, strlen($app_key));
        $binaryKey = base64_decode($base64Key, true);
        if ($binaryKey === false) {
            throw new Exception("Invalid Base64 key");
        }

        $secretKey = substr(hash('sha256', $binaryKey, true), 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        if (file_exists($filePath)) {
            $file = fopen($filePath, "r");
        } else {
            return "Error: File " . $filePath . " does not exist.";
        }

        $fileData = fread($file, filesize($filePath));
        $nonce = substr($fileData,0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        // Double check the lines above and below this because this feels incredibly wrong
        $encryptedMessage = substr($fileData, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, strlen($fileData));
        // return $encryptedMessage;
        return sodium_crypto_secretbox_open($encryptedMessage, $nonce, $secretKey);


    }
}