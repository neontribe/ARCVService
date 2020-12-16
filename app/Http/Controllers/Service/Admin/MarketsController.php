<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewUpdateMarketRequest;
use App\Market;
use App\Sponsor;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketsController extends Controller
{
    public function index()
    {
        $markets = Market::with(['sponsor', 'traders'])->get();
        return view('service.markets.index', compact('markets'));
    }

    public function store(AdminNewUpdateMarketRequest $request)
    {
        try {
            $market = DB::transaction(function () use ($request) {
                // Get our sponsor
                $sponsor = Sponsor::findOrFail($request->input('sponsor'));

                // Create a Market;
                $m = new Market([
                    'name' => $request->input('name'),
                    'sponsor_id' => $sponsor->id,
                    'location' => $sponsor->name,
                    'payment_message' => $request->input('payment_pending')
                ]);
                $m->save();

                return $m;
            });
        } catch (Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('admin.markets.create')->withErrors('Creation failed - DB Error.');
        }
        return redirect()
            ->route('admin.marketa.index')
            ->with('message', 'Market ' . $market->name . ' created');
    }

    public function create()
    {
        $sponsors = Sponsor::get();
        return view('service.markets.create', compact('sponsors'));
    }

    public function edit($id)
    {
        $market = Market::findOrFail($id);
        $sponsors = Sponsor::get();
        return view('service.markets.edit', compact('sponsors', 'market'));
    }

    public function update(AdminNewUpdateMarketRequest $request, $id)
    {
        try {
            $market = DB::transaction(function () use ($request, $id) {
                // Get our sponsor
                $sponsor = Sponsor::findOrFail($request->input('sponsor'));

                // Update a Market;
                $m = Market::findOrFail($id);

                // Update the system
                $m->fill([
                    'name' => $request->input('name'),
                    'sponsor_id' => $sponsor->id,
                    'location' => $sponsor->name,
                    'payment_message' => $request->input('payment_pending')
                ]);
                $m->save();

                return $m;
            });
        } catch (Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.markets.edit', ['id' => $id])
                ->withErrors('Update failed - DB Error.');
        }
        return redirect()
            ->route('admin.markets.index')
            ->with('message', 'Market ' . $market->name . ' updated');
    }
}
