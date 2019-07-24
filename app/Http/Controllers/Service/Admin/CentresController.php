<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Centre;
use App\Sponsor;

class CentresController extends Controller
{

    /**
     * Display a listing of Centres.
     *
     * @return json
     */
    public function index()
    {
        $centres = Centre::get();
        $sponsors = Sponsor::get();

        $data = [
          "centres" => $centres,
          "sponsors" => $sponsors
        ];

        return view('service.centres.centres_view', $data);
    }
}
