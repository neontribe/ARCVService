<?php

namespace App\Http\Controllers\Store;

use App\CentreUser;
use App\Family;
use App\Carer;
use App\Child;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewRegistrationRequest;
use App\Http\Requests\StoreUpdateRegistrationRequest;
use App\Registration;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\Valuation;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use HighSolutions\LaravelSearchy\Facades\Searchy;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Log;
use PDF;
use Throwable;

class RegistrationController extends Controller
{
    /**
     * List all the Registrations (search-ably)
     *
     * This is a con. It only lists the registrations available to a User's CC's Sponsor
     * This means a User can see the Registrations in his 'neighbour' CCs under a Sponsor
     *
     * Also, the view contains the search functionality.
     *
     * @param Request $request
     * @return Factory|View
     */
    public function index(Request $request)
    {
        // Masthead bit
        /** @var User $user */
        $user = Auth::user();
        $programme = Auth::user()->centre->sponsor->programme;
        $data = [
            "user_name" => $user->name,
            "centre_name" => ($user->centre) ? $user->centre->name : null,
            "programme" => $programme,
        ];
        $programme = Auth::user()->centre->sponsor->programme;

        // Slightly roundabout method of getting the permitted centres to poll
        $neighbour_centre_ids = $user
            ->relevantCentres()
            ->pluck('id')
            ->toArray();

        $family_name = $request->get('family_name');

        // Fetch the list of primary carers, the first carer in the family.
        $pri_carers = Carer::select([DB::raw('MIN(id) as min_id')])
            ->groupBy('family_id')
            ->pluck('min_id')
            ->toArray();

        // Get the current database driver
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver == 'mysql') {
            // We can use Searchy for mysql; defaults to "fuzzy" search;
            // results are a collection of basic objects, but we can still "pluck()"
            $filtered_family_ids = Searchy::search('carers')
                ->fields('name')
                ->query($family_name)
                ->getQuery()
                ->whereIn('id', $pri_carers)
                ->pluck('family_id')
                ->toArray();
        } else {
            // We may not be able to use Searchy, so we default to unfuzzy.
            $filtered_family_ids = Carer::whereIn('id', $pri_carers)
                ->where('name', 'like', '%' . $family_name . '%')
                ->pluck('family_id')
                ->toArray();
        }

        //find the registrations
        $q = Registration::query();
        if (!empty($neighbour_centre_ids)) {
            $q = $q->whereIn('centre_id', $neighbour_centre_ids);
        }

        if (!empty($filtered_family_ids)) {
            $q = $q->whereIn('family_id', $filtered_family_ids)
                //  Somehow, whereIn re-orders the filtered array into numeric order.
                //  this would be the "cheap" solution, IF sqlite supported FIELD so we could test that.
                //  ->orderByRaw(DB::raw("FIELD(family_id, " .implode(',', $filtered_family_ids). ")"));
            ;
        }

        // Check if the request asks us to display inactive families
        $q = $request->get('families_left') ? $q : $q->WhereActiveFamily();

        // Check if the request should filter by centre
        $q = $request->get('centre') ? $q->where('centre_id', $request->get('centre')) : $q;

        // This isn't ideal as it relies on getting all the families, then sorting them.
        // However, the whereIn statements above destroy any sorted order on family_ids.
        $reg_models = $q->WithFullFamily()
            ->get()
            ->values();

        // Get any sorting direction from the request
        $direction = $request->get('direction') ? $request->get('direction') : 'asc';

        // Sort by desc if requested to, for anything else sort by asc as usual
        if($direction === 'desc') {
            $reg_models = $reg_models->sortByDesc(function ($registration) {
                return strtolower($registration->family->pri_carer);
            });
        } else {
            $reg_models = $reg_models->sortBy(function ($registration) {
                return strtolower($registration->family->pri_carer);
            });
        }

        // throw it into a paginator.
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $offset = ($page * $perPage) - $perPage;
        $registrations = new LengthAwarePaginator(
            $reg_models->slice($offset, $perPage),
            $reg_models->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => array_except($request->query(), 'page'),
            ]
        );

        $data = array_merge(
            $data,
            [
                'registrations' => $registrations,
                'programme' => $programme,
            ]
        );
        return view('store.index_registration', $data);
    }

    /**
     * Returns the registration page
     *
     * @return Factory|View
     */
    public function create()
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if we verify, based on the currently logged in user's context
        // as we have no registration to refer to yet.
        $sponsor = $user->centre->sponsor;
        $evaluator = EvaluatorFactory::make($sponsor->evaluations);
        $sponsorsRequiresID = $evaluator->isVerifyingChildren();
        $programme = Auth::user()->centre->sponsor->programme;

        $data = [
            "user_name" => $user->name,
            "centre_name" => ($user->centre) ? $user->centre->name : null,
            "sponsorsRequiresID" => $sponsorsRequiresID,
            "programme" => $programme,
            'leaver' => false,
        ];
        return view('store.create_registration', $data);
    }

    /**
     * Show the Registration / Family edit form
     *
     * @param Registration $registration
     * @return Factory|View
     */
    public function edit(Registration $registration)
    {
        // Get User and Centre;
        /** @var CentreUser $user */
        $user = Auth::user();
        $data = [
            'user_name' => $user->name,
            'centre_name' => ($user->centre) ? $user->centre->name : null,
        ];

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        if ($registration) {
            $registration = Registration::withFullFamily()->find($registration->id);
        } else {
            abort(404, 'Registration not found.');
        }
        $evaluations = $registration->centre->sponsor->evaluations;
        $deferrable = false;
        foreach ($evaluations as $key => $evaluation) {
            if ($evaluation['name'] === 'ScottishChildCanDefer') {
                $deferrable = true;
            }
        }
        $can_change_defer = true;
        $this_month = Carbon::now()->month;
        if ($this_month > config('arc.scottish_school_month')) {
            $can_change_defer = false;
        }

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Grab carers copy for shift)ing without altering family->carers
        $carers = $registration->family->carers->all();
		$pri_carer = array_shift($carers);
		$pri_carer_ethnicity = $pri_carer->ethnicity;
		$pri_carer_language = $pri_carer->language;

        $evaluations["creditables"] = $registration->getEvaluator()->getPurposeFilteredEvaluations("credits");
        $evaluations["disqualifiers"] = $registration->getEvaluator()->getPurposeFilteredEvaluations("disqualifiers");
        $programme = Auth::user()->centre->sponsor->programme;


        return view('store.edit_registration', array_merge(
            $data,
            [
                'registration' => $registration,
                'family' => $registration->family,
                'pri_carer' => $pri_carer,
				'pri_carer_ethnicity' => $pri_carer_ethnicity,
				'pri_carer_language' => $pri_carer_language,
                'sec_carers' => $carers,
                'children' => $registration->family->children,
                'noticeReasons' => $valuation->getNoticeReasons(),
                'entitlement' => $valuation->getEntitlement(),
                'sponsorsRequiresID' => $registration->getEvaluator()->isVerifyingChildren(),
                'evaluations' => $evaluations,
                'deferrable' => $deferrable,
                'can_change_defer' => $can_change_defer,
                'programme' => $programme,
                'leaver' => false,
            ]
        ));
    }

    /**
     * Show the Registration / Family view form for a leaver
     *
     * @param Registration $registration
     * @return Factory|View
     */
    public function view(Registration $registration)
    {
        // Get User and Centre;
        /** @var CentreUser $user */
        $user = Auth::user();
        $data = [
            'user_name' => $user->name,
            'centre_name' => ($user->centre) ? $user->centre->name : null,
        ];

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        if ($registration) {
            $registration = Registration::withFullFamily()->find($registration->id);
        } else {
            abort(404, 'Registration not found.');
        }
        $evaluations = $registration->centre->sponsor->evaluations;

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Grab carers copy for shift)ing without altering family->carers
        $carers = $registration->family->carers->all();

        $evaluations["creditables"] = $registration->getEvaluator()->getPurposeFilteredEvaluations("credits");
        $evaluations["disqualifiers"] = $registration->getEvaluator()->getPurposeFilteredEvaluations("disqualifiers");
        $programme = Auth::user()->centre->sponsor->programme;

        return view('store.view_registration', array_merge(
            $data,
            [
                'registration' => $registration,
                'family' => $registration->family,
                'pri_carer' => array_shift($carers),
                'children' => $registration->family->children,
                'entitlement' => $valuation->getEntitlement(),
                'programme' => $programme,
                'leaver' => true,
            ]
        ));
    }

    /**
     * Displays a printable version of the Registration.
     *
     * @param Registration $registration
     * @return Response
     */
    public function printOneIndividualFamilyForm(Registration $registration)
    {
        // Get User
        $user = Auth::user();

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        if ($registration) {
            $registration = Registration::withFullFamily()->find($registration->id);
        } else {
            abort(404, 'Registration not found.');
        }

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Make a filename
        $filename = 'Registration' . Carbon::now()->format('YmdHis') . '.pdf';

        // Setup common data
        $data = [
            'user_name' => $user->name,
            'centre_name' => ($user->centre) ? $user->centre->name : null,
            'sheet_title' => 'Printable Family Sheet',
            'sheet_header' => 'Family Collection Sheet',
        ];

        $data['regs'][] = [
            'centre' => $registration->centre,
            'family' => $registration->family,
            'pri_carer' => $registration->family->pri_carer,
            'children' => $registration->family->children,
            'noticeReasons' => $valuation->getNoticeReasons(),
            'creditReasons' => $valuation->getCreditReasons(),
            'entitlement' => $valuation->getEntitlement(),
        ];

        // throw at a PDF
        $pdf = PDF::loadView('store.printables.family', $data);
        $pdf->setPaper('A4', 'landscape');
        return @$pdf->download($filename);
    }

    /**
     * Displays a printable version of the Registration.
     *
     * @return RedirectResponse|Response
     */
    public function printBatchIndividualFamilyForms()
    {
        // Get the user and Centre
        $user = Auth::user();
        $centre = ($user->centre) ? $user->centre : null;

        // Cope if User has no Centre.
        if (!$centre) {
            Log::info('User ' . $user->id . " has no Centre");
            // Send me back to dashboard
            return redirect()
                ->route('store.dashboard')
                ->withErrors(['error_message' => 'User has no Centre']);
        }
        // Get the registrations this User's centre is directly responsible for
        $registrations = $centre->registrations()
            ->whereActiveFamily()
            ->withFullFamily()
            ->get()
            ->sortBy(function ($registration) {
                // Need strtolower because case comparison sucks.
                return strtolower($registration->family->pri_carer);
            });

        if ($registrations->count() < 1) {
            return redirect()
                ->route('store.dashboard')
                ->with('error_message', 'No Registrations in that centre.');
        }

        // Make a filename
        $filename = 'Registrations_' . Carbon::now()->format('YmdHis') . '.pdf';

        // Set up the common view data.
        $data = [
            'user_name' => $user->name,
            'centre_name' => ($user->centre) ? $user->centre->name : null,
            'sheet_title' => 'Printable Family Sheet',
            'sheet_header' => 'Family Collection Sheet',
        ];

        // Stack the registration batch into the data
        foreach ($registrations as $registration) {
            // Get the valuation
            $valuation = $registration->getValuation();

            $data['regs'][] = [
                'centre' => $centre,
                'family' => $registration->family,
                'pri_carer' => $registration->family->pri_carer,
                'children' => $registration->family->children,
                'noticeReasons' => $valuation->getNoticeReasons(),
                'creditReasons' => $valuation->getCreditReasons(),
                'entitlement' => $valuation->getEntitlement(),
            ];
        }

        // throw it at a PDF.
        $pdf = PDF::loadView(
            'store.printables.family',
            $data
        );
        $pdf->setPaper('A4', 'landscape');
        return @$pdf->download($filename);
    }

    /**
     * Stores an incoming Registration.
     *
     * @param StoreNewRegistrationRequest $request
     * @return RedirectResponse
     * @throws Throwable $e
     */
    public function store(StoreNewRegistrationRequest $request): RedirectResponse
    {
        // Create Carers, merge primary carer
        $carers = array_map(
            function ($carer) use ($request) {
                return new Carer([
					'name' => $carer,
					'ethnicity' => $request->get("pri_carer_ethnicity"),
					'language' => $request->get("pri_carer_language")
				]);
            },
            array_merge(
                (array)$request->get('pri_carer'),
                (array)$request->get('new_carers')
            )
        );

        // Create Children
        $children = $this->makeChildrenFromInput(
            (array)$request->get('children')
        );

        $hsbs = $request->get('eligibility-hsbs');
        $eligible_from = null;
        if ($hsbs === 'healthy-start-receiving') {
            $eligible_from = Carbon::now();
        }

        // Create Registration
        $registration = new Registration([
            'consented_on' => Carbon::now(),
            'eligibility_hsbs' => $request->get('eligibility-hsbs'),
            'eligibility_nrpf' => $request->get('eligibility-nrpf'),
            'eligible_from' => $eligible_from,
        ]);

        // Duplicate families are fine at this point.
        $family = new Family();

        // Set the RVID using the User's Centre.
        $family->lockToCentre(Auth::user()->centre);

        // Try to transact, so we can roll it back
        try {
            DB::transaction(function () use ($registration, $family, $carers, $children) {
                $family->save();
                $family->carers()->saveMany($carers);
                $family->children()->saveMany($children);
                $registration->family()->associate($family);
                $registration->centre()->associate(Auth::user()->centre);
                $registration->save();
            });
        } catch (Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('store.registration.create')->withErrors('Registration failed.');
        }
        // Or return the success
        Log::info('Registration ' . $registration->id . ' created by service user ' . Auth::id());
        // and go to the edit page for the new registration
        return redirect()
            ->route('store.registration.edit', ['registration' => $registration->id])
            ->with('message', 'Registration created.');
    }

    /**
     * Update a Registration
     *
     * @param StoreUpdateRegistrationRequest $request
     * @param Registration $registration
     * @return RedirectResponse
     */
    public function update(StoreUpdateRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        $amendedCarers = [];

        // Fetch eligibility
        $eligibility_hsbs = $request->get('eligibility-hsbs');
        $eligibility_nrpf = $request->get('eligibility-nrpf');
        $eligible_from = $registration->eligible_from;
        $deferred = $request->get('deferred');

        //Prevent the date changing if you're just editing a different field
        if ($registration->eligible_from === null && $eligibility_hsbs === 'healthy-start-receiving') {
            $eligible_from = Carbon::now();
        } else {
            if ($eligibility_hsbs !== 'healthy-start-receiving') {
                $eligible_from = null;
            }
        }

        // NOTE: Following refactor where we needed to retain Carer ids.
        // Possible that we might want to add flag to carer to distinguish Main from Secondary,
        // to simplify method below for sorting and updating carer entries.

        // Update primary carer.
        $carerInput = (array)$request->get("pri_carer");
		$carerEthnicity = $request->get("pri_carer_ethnicity");
		$carerLanguage= $request->get('pri_carer_language');
        $carerKey = key($carerInput);
        $carer = Carer::find($carerKey);
        if ($carer->name !== $carerInput[$carer->id]) {
            $carer->name = $carerInput[$carer->id];
            $amendedCarers[] = $carer;
        }
		if (isset($carer->ethnicity) && $carerEthnicity !== $carerEthnicity[$carer->id]) {
			$carer->ethnicity = $carerEthnicity[$carer->id];
			$amendedCarers[] = $carer;
		}
		if (isset($carer->language) && $carerLanguage !== $carerLanguage[$carer->id]) {
			$carer->language = $carerLanguage[$carer->id];
			$amendedCarers[] = $carer;
		}

        // Find secondary carers id's in the DB
        $carersInput = (array)$request->get("sec_carers");
        $carersKeys = $registration->family->carers->pluck("id")->toArray();
        // remove carerKey from that;
        if (($key = array_search($carerKey, $carersKeys)) !== false) {
            unset($carersKeys[$key]);
        }

        // Those in the DB, not in the input can be scheduled for deletion;
        $carersInputKeys = array_keys($carersInput);
        $carersKeysToDelete = array_diff($carersKeys, $carersInputKeys);

        // Get the secondary carers.
        $carers = Carer::whereIn("id", $carersInputKeys)->get();

        // roll though those and amend them if necessary.
        foreach ($carers as $carer) {
            if ($carer->name !== $carersInput[$carer->id]) {
                $carer->name = $carersInput[$carer->id];
                $amendedCarers[] = $carer;
            }
        }

        // Create new carers
        $newCarers = array_map(
            static function ($new_carer) {
                return new Carer(['name' => $new_carer]);
            },
            (array)$request->get('new_carers')
        );

        // Create New Children
        $children = $this->makeChildrenFromInput(
            (array)$request->get('children')
        );

        $family = $registration->family;

        // Try to transact, so we can roll it back
        try {
            DB::transaction(function () use (
                $registration,
                $family,
                $amendedCarers,
                $newCarers,
                $carersKeysToDelete,
                $children,
                $eligibility_hsbs,
                $eligibility_nrpf,
                $eligible_from
            ) {

                // delete the missing carers
                Carer::whereIn('id', $carersKeysToDelete)->get()->each(function($carer) {
                    $carer->delete();
                });

                // delete the children. still messy.
                $family->children()->delete();

                // save the new ones!
                $family->carers()->saveMany($newCarers);
                $family->children()->saveMany($children);

                // save changes to the changed names
                collect($amendedCarers)->each(
                    function (Carer $model) {
                        $model->save();
                    }
                );

                // update eligibility
                $registration->eligibility_hsbs = $eligibility_hsbs;
                $registration->eligibility_nrpf = $eligibility_nrpf;
                $registration->eligible_from = $eligible_from;

                // save changes to registration.
                $registration->save();
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('store.registration.edit', ['registration' => $registration->id])->withErrors('Registration update failed.');
        }
        // Or return the success
        Log::info('Registration ' . $registration->id . ' updated by service user ' . Auth::id());
        // and go back to edit page for the changed registration
        return redirect()
            ->route('store.registration.edit', ['registration' => $registration->id])
            ->with('message', 'Registration updated.');
    }

    /**
     * Makes children from input data
     * @param array $children
     * @return Child[]
     */
    private function makeChildrenFromInput(array $children = []): array
    {
        return array_map(
            function ($child) {
            // Note: Carbon uses different time formats than laravel validation
            // For crazy reasons known only to the creators of Carbon, when no day provided,
            // createFromFormat - defaults to 31 - which bumps to next month if not a real day.
            // So we want '2013-02-01' not '2013-02-31'...
                $month_of_birth = Carbon::createFromFormat('Y-m-d', $child['dob'] . '-01');

            // Check and set verified, or null
                $verified = null;
                if (array_key_exists('verified', $child)) {
                    $verified = (bool)$child['verified'];
                }

            // Check and set deferred, or null
                $deferred = 0;
                if (array_key_exists('deferred', $child)) {
                    $deferred = (bool)$child['deferred'];
                }

            // Check and set is_pri_carer, or null
                $is_pri_carer = null;
                if (array_key_exists('is_pri_carer', $child)) {
                    $is_pri_carer = (bool)$child['is_pri_carer'];
                }

                return new Child([
                    'born' => $month_of_birth->isPast(),
                    'dob' => $month_of_birth->toDateTimeString(),
                    'verified' => $verified,
                    'deferred' => $deferred,
                    'is_pri_carer' => $is_pri_carer,
                ]);
            },
            $children
        );
    }
}
