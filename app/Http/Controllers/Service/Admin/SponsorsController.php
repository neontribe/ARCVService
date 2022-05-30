<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRulesRequest;
use App\Evaluation;
use App\Sponsor;
use Auth;
use DB;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            // this is the simplest way to do this
            'can_tap' => false,
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

    /**
     * Show the form for editing an SP Sponsor's rule values.
     *
     * @return Factory|View
     */
    public function edit($id)
    {
        $sponsor = Sponsor::find($id);
        $householdExistsValue = $sponsor->evaluations->where('name', 'HouseholdExists')->pluck('value');
        $householdMemberValue = $sponsor->evaluations->where('name', 'HouseholdMember')->pluck('value');
        return view('service.sponsors.edit', compact('sponsor', 'householdExistsValue', 'householdMemberValue'));
    }

    /**
     * Update an SP Sponsor's rule values.
     *
     * @return Factory|View
     */
    public function update(UpdateRulesRequest $request, int $id)
    {
      $sponsor = Sponsor::findOrFail($id);
      try {
          Evaluation::where('sponsor_id', $id)
              ->where('name', 'HouseholdExists')
              ->update(['value' => (int)$request->input('householdExistsValue')]);
          Evaluation::where('sponsor_id', $id)
              ->where('name', 'HouseholdMember')
              ->update(['value' => (int)$request->input('householdMemberValue')]);
          Evaluation::where('sponsor_id', $id)
              ->where('name', 'DeductFromCarer')
              ->update(['value' => (int)$request->input('householdMemberValue') * -1]);
      } catch (Exception $e) {
          // Oops! Log that
          Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
          Log::error($e->getTraceAsString());
          // Throw it back to the user
          return redirect()
              ->route('admin.sponsors.index')
              ->withErrors('Update failed - DB Error.');
      }
      return redirect()
          ->route('admin.sponsors.index')
          ->with('message', 'Sponsor ' . $sponsor->name . ' rule values edited');
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
                "name" => "ScottishChildIsBetweenOneAndPrimarySchoolAge",
                "value" => 4,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            new Evaluation([
                "name" => "ChildIsBetweenOneAndPrimarySchoolAge",
                "value" => null,
                "purpose" => "credits",
                "entity" => "App\Child",
            ]),
            // Scotland has 4 not 3
            new Evaluation([
                "name" => "ScottishChildIsPrimarySchoolAge",
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
                    "name" => "ScottishFamilyHasNoEligibleChildren",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Family",
            ]),
            new Evaluation([
                    "name" => "FamilyHasNoEligibleChildren",
                    "value" => null,
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
            ]),
            new Evaluation([
                    "name" => "ChildIsSecondarySchoolAge",
                    "value" => 0,
                    "purpose" => "disqualifiers",
                    "entity" => "App\Child",
            ]),
        ];
    }
}
