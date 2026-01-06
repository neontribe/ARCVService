<?php

namespace App\Http\Controllers\Service\Admin;

use App\Evaluation;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewSponsorRequest;
use App\Http\Requests\UpdateRulesRequest;
use App\Sponsor;
use Auth;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Log;

class SponsorsController extends Controller
{
    public static function scottishFamilyOverrides(): array
    {
        return Arr::map(Config::get('evaluations.overrides.scottish-family'), static function (array $item) {
            return new Evaluation($item);
        });
    }

    public static function socialPrescribingOverrides(): array
    {
        return Arr::map(Config::get('evaluations.overrides.social-prescribing', []), static function (array $item) {
            return new Evaluation($item);
        });
    }

    public function index(): Factory|View
    {
        $sponsors = Sponsor::get();

        return view('service.sponsors.index', compact('sponsors'));
    }

    public function create(): Factory|View
    {
        return view('service.sponsors.create');
    }

    public function store(AdminNewSponsorRequest $request): RedirectResponse
    {
        // Validation done already
        $sponsor = new Sponsor([
            'name' => $request->input('name'),
            'shortcode' => $request->input('voucher_prefix'),
            // this is the simplest way to do this
            'can_tap' => false,
            'programme' => $request->input('programme'),
        ]);

        // Atomic action,don't need to transact it
        if (!$sponsor->save()) {
            // Oops! Log that
            Log::error('Bad save for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            // Throw it back to the user
            return redirect()->route('admin.sponsors.create')->withErrors('Creation failed - DB Error.');
        }

        // Send to index with a success message
        return redirect()->route('admin.sponsors.index')->with('message', 'Sponsor ' . $sponsor->name . ' created.');
    }

    public function edit($id): Factory|View
    {
        $validation = Validator::make(['id' => $id], [
            'id' => [
                'required',
                'integer',
                Rule::exists('sponsors', 'id')->where('programme', 1),
            ],
        ]);
        if ($validation->fails()) {
            abort(404);
        }
        $sponsor = Sponsor::find($id);
        $householdExistsValue = $sponsor?->evaluations->where('name', 'HouseholdExists')->first()->value ?? 0;
        $householdMemberValue = $sponsor?->evaluations->where('name', 'HouseholdMember')->first()->value ?? 0;
        return view('service.sponsors.edit', compact('sponsor', 'householdExistsValue', 'householdMemberValue'));
    }

    public function update(UpdateRulesRequest $request, int $id): RedirectResponse
    {
        $sponsor = Sponsor::findOrFail($id);

        $updates = collect($request->only(['householdExistsValue', 'householdMemberValue']))
            // remove null entries
            ->reject(function ($value) {
                return is_null($value);
            })
            // turn it into keys
            ->mapWithKeys(function ($value, $key) {
                return match ($key) {
                    'householdExistsValue' => ['HouseholdExists' => (int)$value],
                    'householdMemberValue' => [
                        'HouseholdMember' => (int)$value,
                        'DeductFromCarer' => (int)$value * -1,
                    ],
                    default => [],
                };
            });

        $evals = collect(Config::get('evaluations.overrides.social-prescribing', []));

        foreach ($updates as $key => $value) {
            // get the defaults
            $default = $evals->firstWhere('name', $key);

            if (!$default) {
                continue;
            }

            $payload = array_merge($default, [
                'value' => $value,
                'sponsor_id' => $id,
            ]);

            try {
                Evaluation::updateOrCreate(['sponsor_id' => $id, 'name' => $key], $payload);
            } catch (Exception $e) {
                Log::error("Failed to update evaluation $key for sponsor #{$id} by user " . Auth::id(), [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->route('admin.sponsors.index')->withErrors('Update failed - DB Error.');
            }
        }
        return redirect()->route('admin.sponsors.index')->with('message',
                'Sponsor ' . $sponsor->name . ' rule values edited');
    }
}
