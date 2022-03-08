<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;

class VersionController extends Controller
{
    public function version(): JsonResponse
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $pkg = json_decode(file_get_contents(base_path('composer.json')), false);

        return response()->json(['Service/API' => $pkg->version]);
    }
}
