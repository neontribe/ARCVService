<?php

namespace App\Http\Controllers\API;

use App\Wrappers\SecretStreamWrapper;
use App\Http\Controllers\Controller;
use App\MarketLog;
use False\True;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;

class LoggingController extends Controller
{

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

        // Rewriting JSON as CSV to storage/logs/market_logs.csv
        // TODO: Test this.
        // TODO: Add method to decrypt the CSV.
        $processed = [];
        if (!empty($json)) {

            $headers = ["hash", "url", "status", "created", "data","trader-id"];
            $csvFilePath = "logs/market_logs.csv";

            // Add encryption wrapper
            if (!in_array("ssw", stream_get_wrappers())) {
                stream_wrapper_register("ssw", "App\Wrappers\SecretStreamWrapper");
            }

            $csvFile = fopen("ssw://" . $csvFilePath, "a");

            if (!$csvFile) {
                Log::error("Unable to open CSV file.");
                return response()->json();
            }

            $csvIsEmpty = (filesize($csvFilePath) === 0);
            if ($csvIsEmpty) {
                fputcsv($csvFile, $headers);
            }

            foreach ($json as $hash => $item) {
                $url = $item['config']['url'] ?? '';
                $status = $item['status'] ?? -1;
                $created = $item['created'] ?? '' ;
                $jsonData = json_encode($item);
                $trader_id = $item['trader'] ?? -1;
                $processed[] = $hash;

                fputcsv($csvFile, [$hash, $url, $status, $created, $jsonData, $trader_id]);
            }

            stream_wrapper_unregister("ssw");
        }

        return response()->json($processed);
    }
}
