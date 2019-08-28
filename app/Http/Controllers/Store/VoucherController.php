<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use URL;
use ZipArchive;
use ZipStream\ZipStream;

class VoucherController extends Controller
{
    // This belongs here because it's largely about arranging vouchers
    public function exportMasterVoucherLog()
    {
        $dashboard_route = URL::route('store.dashboard');

        // Setup the Storage dir
        $disk = Storage::disk(config('arc.mvl_disk'));
        $archiveName = config('arc.mvl_filename');

        // Check it's there.
        if (!$disk->exists($archiveName)) {
            // Return to dashboard with an error that indicates we don't have a file.
            return redirect($dashboard_route)
                ->with('error_message', "Sorry, couldn't find a current export. Please check the exporter ran on schedule");
        }

        // Open and test archive.
        $storedZip = new ZipArchive();

        // ZipArchive likes a fully qualified path.
        $check = $storedZip->open($disk->path($archiveName), ZipArchive::CHECKCONS);

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
                $disk->url($archiveName) .
                "' as : " .
                $message
            );

            // go back to dashboard with a
            return redirect($dashboard_route)
                ->with('error_message', "Sorry, the export file was unreadable. Please contact support.");
        }

        // Inject the original file last_modified into the d/l file name.
        $filename =  pathinfo($archiveName, PATHINFO_BASENAME) .
            "_" . strftime('%Y-%m-%d_%H%M', $disk->lastModified($archiveName)) .
            "." . pathinfo($archiveName, PATHINFO_EXTENSION)
        ;

        return new StreamedResponse(function () use ($storedZip) {
            // Serve the user a zip with all of the files.
            // We stream this to avoid duplicating everything in memory.
            $zip = new ZipStream(null, [
                ZipStream::OPTION_SEND_HTTP_HEADERS => false,
                ZipStream::OPTION_OUTPUT_STREAM => fopen('php://output', 'w+'),
            ]);

            // Iterate through files in the zip.
            // TODO : Change to $zip->count() if we move to PHP 7.2
            for ($i = 0; $i < $storedZip->numFiles; $i++) {
                // Get the file's actual name.
                $sourceName = $storedZip->getNameIndex($i);

                // Open the stream for that file to decrypt it individually.
                $fileStream = $storedZip->getStream($sourceName);
                if (!$fileStream) {
                    Log::error("ZipArchive cannot read compressed item '" . $sourceName . "'' by stream");
                    continue;
                }

                // Read the file contents to memory; bit of a farce but we don't want to extract to a temp file.
                // TODO : Can we stream encryption / decryption to reduce memory usage to a buffer?
                $contents = '';
                while (!feof($fileStream)) {
                    $contents .= fread($fileStream, 8192);
                }

                // Shut that off.
                fclose($fileStream);

                // Decrypt the file, if necessary.
                if (pathinfo($sourceName, PATHINFO_EXTENSION) == 'enc') {
                    $contents = decrypt($contents);
                    $destName = pathinfo($sourceName, PATHINFO_FILENAME);
                } else {
                    $destName = $sourceName;
                }

                // No compression, to start with.
                $zip->addFile($destName, $contents, [], "store");
            }

            // Finish the Zip.
            $zip->finish();
        }, 200, [
            'Content-Type' => 'application/x-zip',
            'Content-Disposition' => 'attachment; filename="'. $filename .'"',
            'Expires' => Carbon::createFromTimestamp(0)->format('D, d M Y H:i:s'),
            'Last-Modified' => Carbon::now()->format('D, d M Y H:i:s'),
            'Cache-Control' => 'private, no-cache',
            'Pragma' => 'no-cache',
        ]);
    }
}