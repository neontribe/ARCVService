<?php

namespace App\Services;

class TextFormatter
{
    /**
     * Takes a big number, e.g.3261416 and turns it into a human-readable string as a representation of bytes.
     * e.g.
     * ```
     * echo TextFormatter::formatBytes(5116);
     * echo TextFormatter::formatBytes(3261416);
     * echo TextFormatter::formatBytes(16322070752);
     * ```
     *
     * Output:
     * ```
     * 5 KB
     * 3.11 MB
     * 15.2 GB
     * ```
     *
     * @param $bytes int The big number to format
     * @param $precision int Number of decimal places to include
     * @return string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Calculate the bytes value in the chosen unit
        $bytes /= (1 << (10 * $pow));

        // Format the number with the specified precision
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}