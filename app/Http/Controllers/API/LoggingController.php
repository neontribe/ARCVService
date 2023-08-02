<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\MarketLog;
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
            Log::error("Unable to process content as JSON:" . $e->getMessage());
            return response()->json([]);
        }

        $processed = [];
        if (!empty($json)) {
            foreach ($json as $hash => $item) {
                // write to DB
                $marketLog = new MarketLog();
                $marketLog->hash = $hash;
                $marketLog->url = $item["config"]["url"] ?? '';
                $marketLog->status = $item["status"] ?? '';
                $marketLog->created = $item['created'] ?? '';
                $marketLog->data = json_encode($item);
                $marketLog->trader_id = $item['trader'] ?? -1;
                $marketLog->save();
                $processed[] = $hash;
            }
        }
        return response()->json($processed);
    }
}
