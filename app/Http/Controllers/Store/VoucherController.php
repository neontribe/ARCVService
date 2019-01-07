<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Log;
use phpseclib\System\SSH\Agent\Identity;
use ZipArchive;

class VoucherController extends Controller
{
    // This belongs here because it's largely about arranging vouchers
    public function exportMasterVoucherLog()
    {

        $fileAsString = $this->fetchArchive();

        return response('', 200);
    }

    /**
     * Recovers the archive from storage decrypts the data and re-zips it, returning the zip as a file
     * @param string $archiveName The name of the archive to find
     * @param string $disk The place we should look
     * @return string|Bool
     */
    public static function fetchArchive($disk = 'enc', $archiveName = 'MVLReport.zip')
    {
        //Check it's there.
        if (!Storage::disk($disk)->exists($archiveName)) {
            return false;
        }

        // Open and test archive.
        $storedZip = new ZipArchive();

        // ZipArchive likes a fully qualified path.
        $check = $storedZip->open(Storage::disk($disk)->path($archiveName), ZipArchive::CHECKCONS);

        if ($check !== true) {
            switch ($check) {
                case ZipArchive::ER_NOZIP:
                    $message = ('not a zip archive');
                    break;
                case ZipArchive::ER_INCONS:
                    $message = ('consistency check failed');
                    break;
                case ZipArchive::ER_CRC:
                    $message = ('checksum failed');
                    break;
                default:
                    $message = ('error ' . $check);
            }

            // Log that error
            Log::error(
                // Leading `.` as url() gives a leading `/` and the storage is not off root
                "ZipArchive cannot open archive at '." .
                // Using url() to prevent revealing the disk tree
                Storage::disk($disk)->url('$archiveName') .
                "' as : " .
                $message
            );

            return false;
        }

        // Open a new file in memory for output;
        $zaOut = new ZipArchive();

        // Iterate through files in the zip.
        // TODO : Change to $zip->count() if we move to PHP 7.2
        for ($i = 0; $i < $storedZip->numFiles; $i++) {
            // Get the file's name
            $sourceName = $storedZip->getNameIndex($i);
            // Open the stream
            $fileStream = $storedZip->getStream($sourceName);
            if (!$fileStream) {
                Log::error("ZipArchive cannot read compressed item by stream");
            }
            // Get the file contents to memory
            $contents = '';
            while (!feof($fileStream)) {
                $contents = fread($fileStream, 8192);
            }
            fclose($fileStream);
            if (pathinfo($sourceName, PATHINFO_EXTENSION)  == 'enc') {
                $contents = decrypt($contents);
                $destName = pathinfo($sourceName, PATHINFO_FILENAME);
            } else {
                $destName = $sourceName;
            }
        }

        return $zaString;
    }
}