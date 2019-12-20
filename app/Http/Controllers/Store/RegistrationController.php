<?php

namespace App\Http\Controllers\Store;

use App\Family;
use App\Carer;
use App\Child;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewRegistrationRequest;
use App\Http\Requests\StoreUpdateRegistrationRequest;
use App\Registration;
use App\Services\VoucherEvaluator\Valuation;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Log;
use PDF;
use Searchy;
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
        $data = [
            "user_name" => $user->name,
            "centre_name" => ($user->centre) ? $user->centre->name : null,
        ];

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


        // This isn't ideal as it relies on getting all the families, then sorting them.
        // However, the whereIn statements above destroy any sorted order on family_ids.
        $reg_models = $q->withFullFamily()
            ->get()
            ->sortBy(function ($registration) {
                return strtolower($registration->family->pri_carer);
            })->values();

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
        $data = [
            "user_name" => $user->name,
            "centre_name" => ($user->centre) ? $user->centre->name : null,
        ];
        return view('store.create_registration', $data);
    }

    /**
     * Show the Registration / Family edit form
     *
     * @param integer $id
     * @return Factory|View
     */
    public function edit($id)
    {
        // Get User and Centre;
        // TODO: turn this into a masthead view composer on the app service provider.
        $user = Auth::user();
        $data = [
            'user_name' => $user->name,
            'centre_name' => ($user->centre) ? $user->centre->name : null,
        ];

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        $registration = Registration::withFullFamily()->find($id);

        if (!$registration) {
            abort(404, 'Registration not found.');
        }

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Grab carers copy for shift)ing without altering family->carers
        $carers = $registration->family->carers->all();

        return view('store.edit_registration', array_merge(
            $data,
            [
                'registration' => $registration,
                'family' => $registration->family,
                'pri_carer' => array_shift($carers),
                'sec_carers' => $carers,
                'children' => $registration->family->children,
                'noticeReasons' => $valuation->getNoticeReasons(),
                'entitlement' => $valuation->getEntitlement(),
            ]
        ));
    }

    /**
     * Displays a printable version of the Registration.
     *
     * @param integer $id
     * @return Response
     */
    public function printOneIndividualFamilyForm($id)
    {
        // Get User
        $user = Auth::user();

        // Find the Registration and subdata
        $registration = Registration::withFullFamily()->find($id);

        if (!$registration) {
            abort(404, 'Registration not found.');
        }

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Make a filename
        $filename = 'Registration' . Carbon::now()->format('YmdHis') .'.pdf';

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
            'entitlement' => $valuation->getEntitlement()
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
                'entitlement' => $valuation->getEntitlement()
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
     * @throws Throwable $e
     * @return RedirectResponse
     */
    public function store(StoreNewRegistrationRequest $request)
    {
        // Create Carers
        // TODO: Alter request to pre-join the array?
        $carers = array_map(
            function ($carer) {
                return new Carer(['name' => $carer]);
            },
            array_merge(
                (array)$request->get('carer'),
                (array)$request->get('carers')
            )
        );

        // Create Children
        $children = $this->makeChildrenFromInput(
            (array)$request->get('children')
        );

        // Create Registration
        $registration = new Registration([
            'consented_on' => Carbon::now(),
            'eligibility' => $request->get('eligibility')
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
        } catch (\Exception $e) {
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
            ->route('store.registration.edit', ['id' => $registration->id])
            ->with('message', 'Registration created.');
    }

    /**
     * Update a Registration
     *
     * @param StoreUpdateRegistrationRequest $request
     * @return RedirectResponse
     */
    public function update(StoreUpdateRegistrationRequest $request)
    {
        $amendedCarers = [];

        $user = $request->user();

        // Fetch Registration and Family
        $registration = Registration::findOrFail($request->get('registration'));

        // NOTE: Following refactor where we needed to retain Carer ids.
        // Possible that we might want to add flag to carer to distinguish Main from Secondary,
        // to simplify method below for sorting and updating carer entries.

        // Update primary carer.
        $carerInput = (array) $request->get("pri_carer");
        $carerKey = key($carerInput);
        $carer = Carer::find($carerKey);
        if ($carer->name !== $carerInput[$carer->id]) {
            $carer->name = $carerInput[$carer->id];
            $amendedCarers[] = $carer;
        }

        // Find secondary carers id's in the DB
        $carersInput = (array) $request->get("sec_carers");
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
            function ($new_carer) {
                return new Carer(['name' => $new_carer]);
            },
            (array)$request->get('new_carers')
        );

        // Create New Children
        $children = $this->makeChildrenFromInput(
            (array)$request->get('children')
        );

        // Grab the date
        $now = Carbon::now();

        $family = $registration->family;

        // Try to transact, so we can roll it back
        try {
            DB::transaction(function () use ($registration, $family, $amendedCarers, $newCarers, $carersKeysToDelete, $children) {

                // delete the missing carers
                Carer::whereIn('id', $carersKeysToDelete)->delete();

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

                // save changes to registration.
                $registration->save();
            });

        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('store.registration.edit')->withErrors('Registration update failed.');
        }
        // Or return the success
        Log::info('Registration ' . $registration->id . ' updated by service user ' . Auth::id());
        // and go back to edit page for the changed registration
        return redirect()
            ->route('store.registration.edit', ['id' => $registration->id])
            ->with('message', 'Registration updated.');
    }

    /**
     * Makes children from input data
     * @param array $children
     * @return array
     */
    private function makeChildrenFromInput(array $children = [])
    {
        return array_map(function($child) {
            // Note: Carbon uses different time formats than laravel validation
            // For crazy reasons known only to the creators of Carbon, when no day provided,
            // createFromFormat - defaults to 31 - which bumps to next month if not a real day.
            // So we want '2013-02-01' not '2013-02-31'...
            $month_of_birth = Carbon::createFromFormat('Y-m-d', $child['dob'] . '-01');

            // Check and set verified, or null
            $verified = null;
            if (array_key_exists('verified', $child)) {
                $verified = boolval($child('verified'));
            }

            return new Child([
                'born' => $month_of_birth->isPast(),
                'dob' => $month_of_birth->toDateTimeString(),
                'verified' => $verified
            ]);
        },
        $children);
    }
}
