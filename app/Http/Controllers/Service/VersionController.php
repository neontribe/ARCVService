<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;

class VersionController extends Controller
{
    public function version(): JsonResponse
    {
        $version = "unknown";
        if (file_exists(base_path("version.txt"))) {
            $version = trim(file_get_contents(base_path("version.txt")));
        } else {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $pkg = json_decode(file_get_contents(base_path('composer.json')), false);
            $version = $pkg->version;
        }
        return response()->json(['Service/API' => $version]);
    }
}
