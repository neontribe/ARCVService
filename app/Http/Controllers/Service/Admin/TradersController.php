<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewTraderRequest;
use App\Http\Requests\AdminUpdateTraderRequest;
use App\Trader;
use App\Sponsor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class TradersController extends Controller
{
    /**
     * List Traders
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $traders = Trader::with(['users', 'market'])->get();
        return view('service.traders.index', compact('traders'));
    }

    /**
     * Store a new Trader
     *
     * @param AdminNewTraderRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(AdminNewTraderRequest $request)
    {
        try {
            $trader = DB::transaction(function () use ($request) {
                // do store
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('admin.traders.create')->withErrors('Creation failed - DB Error.');
        }
        return redirect()
            ->route('admin.traders.index')
            ->with('message', 'Trader ' . $trader->name . ' created');
    }

    /**
     * Show the create form
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        // Get our list of markets, like centres
        $marketsBySponsor = Sponsor::with(['markets:sponsor_id,id,name'])->get(['id', 'name']);
        return view('service.traders.create', compact('marketsBySponsor'));
    }

    /**
     * Show the edit form
     *
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
        $trader = Trader::findOrFail($id)->with('users');
        $marketsBySponsor = Sponsor::with(['markets:sponsor_id,id,name'])->get(['id', 'name']);
        return view('service.traders.edit', compact('marketsBySponsor', 'trader'));
    }

    /**
     * Update the trader AND set the users
     *
     * @param AdminUpdateTraderRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(AdminUpdateTraderRequest $request, int $id)
    {
        try {
            $trader = DB::transaction(function () use ($request, $id) {
                // Do update
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.traders.edit', ['id' => $id])
                ->withErrors('Update failed - DB Error.');
        }
        return redirect()
            ->route('admin.traders.index')
            ->with('message', 'Trader ' . $trader->name . ' updated');
    }
}
