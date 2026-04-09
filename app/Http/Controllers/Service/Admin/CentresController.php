<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewCentreRequest;
use App\Http\Requests\AdminUpdateCentreRequest;
use App\Sponsor;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class CentresController extends Controller
{
    /**
     * Display a listing of Centres.
     */
    public function index(): View
    {
        $centres = Centre::all();

        return view('service.centres.index', compact('centres'));
    }

    /**
     * Show the form for creating new Centres.
     */
    public function create(): View
    {
        $sponsors = Sponsor::all();

        return view('service.centres.create', compact('sponsors'));
    }

    /**
     * Return a JSON list of neighbour names and IDs.
     */
    public function getNeighboursAsJson(int $id): JsonResponse
    {
        try {
            $neighbours = Centre::findOrFail($id)
                ->neighbours()
                ->whereKeyNot($id)
                ->get(['name', 'id']);
        } catch (ModelNotFoundException) {
            $neighbours = collect();
        }

        return response()->json($neighbours);
    }

    /**
     * Store a newly created Centre.
     */
    public function store(AdminNewCentreRequest $request): RedirectResponse
    {
        try {
            $centre = DB::transaction(static function () use ($request): Centre {
                return Centre::create($request->validated());
            });
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());

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
     */
    public function edit(Centre $centre): View
    {
        return view('service.centres.edit', compact('centre'));
    }

    /**
     * Update the specified Centre's name.
     */
    public function update(AdminUpdateCentreRequest $request, Centre $centre): RedirectResponse
    {
        try {
            $centre = DB::transaction(static function () use ($request, $centre): Centre {
                $centre->fill([
                    'name' => $request->input('name'),
                ]);
                $centre->save();

                return $centre;
            });
        } catch (Throwable $e) {
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());

            return redirect()
                ->route('admin.centres.edit', $centre->id)
                ->withErrors('Update failed - DB Error.');
        }

        return redirect()
            ->route('admin.centres.index')
            ->with('message', 'Centre ' . $centre->name . ' edited');
    }
}
