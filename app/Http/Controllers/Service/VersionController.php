<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cookie;

class VersionController extends Controller
{
    public function index()
    {
        // accept_cookies are deprecated, remove for anyone who has one set
        return response(view('welcome'))->cookie(Cookie::forget('accept_cookies'));
    }

    public function version(): JsonResponse
    {
        /** @noinspection PhpComposerExtensionStubsInspection */
        $pkg = json_decode(file_get_contents(base_path('package.json')), false);

        return response()->json(['Service/API' => $pkg->version]);
    }
}
