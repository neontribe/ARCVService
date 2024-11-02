<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;

class LoggingController extends Controller
{
    public const LOG_NAME = 'market-app.log';

    public function log(Request $request): JsonResponse
    {
        // Doesn't work, why not?
        // $json = $request->json();

        try {
            $json = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error('Unable to process content as JSON:' . $e->getMessage());
            return response()->json([]);
        }

        $path = Storage::disk('log')->path(self::LOG_NAME);
        $fh = fopen($path, "a");

        $processed = [];
        if (!empty($json)) {
            foreach ($json as $hash => $item) {
                fputcsv($fh, [
                    $hash,
                    $item['config']['url'] ?? '',
                    $item['status'] ?? -1,
                    $item['created'] ?? '',
                    json_encode($item),
                    $item['trader'] ?? -1,
                ]);
                $processed[] = $hash;
            }
        }
        fclose($fh);
        return response()->json($processed);
    }
}
