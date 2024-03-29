<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewCentreRequest;
use App\Http\Requests\AdminUpdateCentreRequest;
use Auth;
use DB;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Sponsor;
use Log;

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

    /**
     * @param AdminNewCentreRequest $request
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function store(AdminNewCentreRequest $request)
    {
        try {
            $centre = DB::transaction(function () use ($request) {

                // Create a Centre
                $c = new Centre([
                    'name' => $request->input('name'),
                    'prefix' => $request->input('rvid_prefix'),
                    'print_pref' => $request->input('print_pref'),
                    'sponsor_id' => $request->input('sponsor')
                ]);
                $c->save();

                return $c;
            });
        } catch (Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.centres.create')
                ->withErrors('Creation failed - DB Error.');
        }
        return redirect()
            ->route('admin.centres.index')
            ->with('message', 'Centre ' . $centre->name . ' created');
    }

    /**
     * Show the form for editing a Centre.
     *
     * @return Factory|View
     */
    public function edit($id)
    {
        $centre = Centre::find($id);
        return view('service.centres.edit', compact('centre'));
    }

    /**
     * Show the form for editing a Centre's name.
     *
     * @return Factory|View
     */
    public function update(AdminUpdateCentreRequest $request, Centre $id)
    {
      try {
          $centre = DB::transaction(function () use ($request, $id) {
            // Update the system
            $id->fill([
                'name' => $request->input('name'),
            ]);
              $id->save();

              return $id;
          });
      } catch (Exception $e) {
          // Oops! Log that
          Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
          Log::error($e->getTraceAsString());
          // Throw it back to the user
          return redirect()
              ->route('admin.centres.create')
              ->withErrors('Creation failed - DB Error.');
      }
      return redirect()
          ->route('admin.centres.index')
          ->with('message', 'Centre ' . $centre->name . ' edited');
    }
}
