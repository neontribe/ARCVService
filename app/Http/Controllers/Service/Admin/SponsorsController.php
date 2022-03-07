<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewSponsorRequest;
use App\Evaluation;
use App\Sponsor;
use Auth;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
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
     * @return RedirectResponse
     */
    public function store(AdminNewSponsorRequest $request)
    {
        // Validation done already
        $sponsor = new Sponsor([
            'name' => $request->input('name'),
            'shortcode' => $request->input('voucher_prefix'),
            // 'is_scotland' => $request->input('is_scotland') == 'on' ? 1 : 0
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

        // if ($sponsor->is_scotland) {
        //   $sponsor->evaluations()->saveMany($this->scottishFamilyOverrides());
        // }

        // Send to index with a success message
        return redirect()
            ->route('admin.sponsors.index')
            ->with('message', 'Sponsor ' . $sponsor->name . ' created.');
    }

    public static function scottishFamilyOverrides()
    {
        return [
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "FamilyIsPregnant",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Family",
            ]),
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => "4",
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // Turn off ChildIsPrimarySchoolAge rule
            new Evaluation([
                "name" => "ChildIsPrimarySchoolAge",
                "value" => null,
                "purpose" => "disqualifiers",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                    "name" => "FamilyHasNoEligibleChildren",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Family",
            ]),
            // Needs a different check than England
            new Evaluation([
                    "name" => "ScottishChildIsAlmostPrimarySchoolAge",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            // Get rid of this rule
            new Evaluation([
                    "name" => "ChildIsAlmostPrimarySchoolAge",
                    "value" => NULL,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            // New rule for Scotland
            new Evaluation([
                    "name" => "ScottishChildCanDefer",
                    "value" => 0,
                    "purpose" => "notices",
                    "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "FamilyHasUnverifiedChildren",
                "value" => 0,
                "purpose" => "notices",
                "entity" => "App\Family",
            ])
        ];
    }
}
