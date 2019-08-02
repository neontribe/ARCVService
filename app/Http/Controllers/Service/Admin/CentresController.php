<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Sponsor;

class CentresController extends Controller
{

    /**
     * Display a listing of Centres.
     *
     * @return Factory|View
     */
    public function index()
    {
        $centres = Centre::get();

        return view('service.centres.index', compact('centres'));
    }

    /**
     * Show the form for creating new Centres.
     *
     * @return Factory|View
     */
    public function create()
    {
        $sponsors = Sponsor::get();

        return view('service.centres.create', compact('sponsors'));
    }

    /**
     * Return a json list of neighbour names and IDs
     *
     * @param $id
     * @return JsonResponse
     */
    public function getNeighboursAsJson($id)
    {
        try {
            /** @var Centre $centre */
            $centre = Centre::findOrFail($id);
            $neighbours = $centre
                ->neighbours()
                ->whereKeyNot($id)
                ->get(['name', 'id'])
            ;
        } catch (ModelNotFoundException $e) {
            $neighbours = collect([]);
        }
        return response()->json($neighbours);
    }
}
