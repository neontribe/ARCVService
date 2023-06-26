<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\MarketLog;
use App\Trader;
use Illuminate\Http\Request;

//use Illuminate\Support\Facades\Request;

class LoggingController extends Controller
{

    public function log(Request $request): \Illuminate\Http\JsonResponse
    {
        // Doesn't work, why not?
        // $json = $request->json();
        $json = json_decode($request->getContent(), true);

        $trader = false;
        $processed = [];
        foreach ($json as $hash => $item) {
            if (!$trader) {
                // TODO: add this as path parameter
                $tid = explode("/", $item["config"]["url"])[1];
                $trader = Trader::find($tid);
            }
            // write to DB
            $marketLog = new MarketLog();
            $marketLog->hash = $hash;
            $marketLog->url = $item["config"]["url"];
            $marketLog->status = $item["status"];
            $marketLog->created = $item['created'];
            $marketLog->data = json_encode($item);
            $marketLog->trader = $trader;
            $marketLog->save();
            $processed[] = $hash;
        }

        return response()->json($processed);
    }
}