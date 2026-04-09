<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewCentreRequest;
use App\Http\Requests\AdminUpdateCentreRequest;
use App\Sponsor;
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
    public function getNeighboursAsJson(Centre $centre): JsonResponse
    {
        $neighbours = $centre
            ->neighbours()
            ->whereKeyNot($centre->getKey())
            ->get(['name', 'id']);

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
        $sponsors = Sponsor::all();
        return view('service.centres.edit', compact('centre', 'sponsors'));
    }

    /**
     * Update the specified Centre's fields
     */
    public function update(AdminUpdateCentreRequest $request, Centre $centre): RedirectResponse
    {
        try {
            DB::transaction(static function () use ($request, $centre): bool {
                return $centre->update($request->validated());
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
