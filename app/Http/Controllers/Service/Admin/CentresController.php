<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

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
     * Return a json list of neighbor names and IDs
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNeighborsAsJson($id)
    {
        try {
            /** @var Centre $centre */
            $centre = Centre::findOrFail($id);
            $neighbors = $centre->neighbors()->pluck('name', 'id');
        } catch (ModelNotFoundException $e) {
            $neighbors = collect([]);
        }
        return response()->json($neighbors);
    }
}
