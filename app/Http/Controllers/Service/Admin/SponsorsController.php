<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewSponsorRequest;
use App\Sponsor;
use Auth;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Log;

class SponsorsController extends Controller
{
    /**
     * Display a listing of Sponsors.
     *
     * @return Factory|View
     */
    public function index()
    {
        $sponsors = Sponsor::get();

        return view('service.sponsors.index', compact('sponsors'));
    }

      /**
     * Show the form for creating new Sponsors.
     *
     * @return Factory|View
     */
    public function create()
    {
        return view('service.sponsors.create');
    }

    /**
     * Store a Sponsor
     *
     * @param AdminNewSponsorRequest $request
     * @return \Illuminate\Http\RedirectResponse#
     */
    public function store(AdminNewSponsorRequest $request)
    {
        // Validation done already
        $sponsor = new Sponsor([
            'name' => $request->input('name'),
            'shortcode' => $request->input('voucher_prefix'),
        ]);
        // Atomic action,don't need to transact it
        if (!$sponsor->save()) {
            // Oops! Log that
            Log::error('Bad save for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            // Throw it back to the user
            return redirect()
                ->route('admin.sponsors.create')
                ->withErrors('Creation failed - DB Error.');
        }
        // Send to index with a success message
        return redirect()
            ->route('admin.sponsors.index')
            ->with('message', 'Sponsor ' . $sponsor->name . ' created.');
    }
}
