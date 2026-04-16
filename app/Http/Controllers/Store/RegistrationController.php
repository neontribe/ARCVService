<?php

namespace App\Http\Controllers\Store;

use App\Carer;
use App\CentreUser;
use App\Child;
use App\Family;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewRegistrationRequest;
use App\Http\Requests\StoreUpdateRegistrationRequest;
use App\Registration;
use App\Services\VoucherEvaluator\EvaluatorFactory;
use App\Services\VoucherEvaluator\Valuation;
use App\User;
use Carbon\Carbon;
use HighSolutions\LaravelSearchy\Facades\Searchy;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use PDF;
use Throwable;

class RegistrationController extends Controller
{
    public function index(Request $request): Factory|View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $familyName = $request->string('family_name');
        $fuzzy = $request->boolean('fuzzy');
        $descending = $request->input('direction') === 'desc';
        $neighbourCentreIds = $user->relevantCentres()->pluck('id');

        $baseQuery = Registration::query()
            ->withPrimaryCarer()
            ->whereIn('registrations.centre_id', $neighbourCentreIds);

        if (
            $user->centres->count() > 1 &&
            $request->boolean('filter_by_centre') &&
            ($centreId = session('CentreUserCurrentCentreId'))
        ) {
            $baseQuery->where('registrations.centre_id', $centreId);
        }

        if (!$request->boolean('families_left')) {
            $baseQuery->WhereActiveFamily();
        }

        $useFuzzy = $fuzzy
            && $familyName->isNotEmpty()
            && config('database.connections.' . config('database.default') . '.driver') === 'mysql';

        [$registrations, $resolvedFuzzy] = $useFuzzy
            ? [$this->fetchFuzzy($request, $baseQuery, $familyName, $neighbourCentreIds, $descending), true]
            : [$this->fetchExact($baseQuery, $familyName, $descending), false];

        if ($registrations->currentPage() > $registrations->lastPage()) {
            return redirect()->to(
                $registrations->url($registrations->lastPage())
            );
        }

        return view('store.index_registration', [
            'user_name' => $user->name,
            'centre_name' => $user->centre?->name,
            'programme' => $user->centre?->sponsor?->programme,
            'registrations' => $registrations,
            'fuzzy' => $resolvedFuzzy,
        ]);
    }

    /**
     * Exact-match strategy: everything resolved in SQL.
     * Returns a paginator ready for the view.
     */
    private function fetchExact(
        Builder $query,
        Stringable $familyName,
        bool $descending,
    ): LengthAwarePaginator {
        if ($familyName->isNotEmpty()) {
            $query->filterByCarerName((string)$familyName);
        }

        $query->orderByCarerName($descending);

        return $query
            ->WithFullFamily()
            ->paginate(perPage: 10)
            ->withQueryString();
    }

    /**
     * Fuzzy strategy (MySQL-only): Searchy ranks IDs by relevance;
     * ordering is preserved via PHP-side pagination.
     * Returns a paginator ready for the view.
     */
    private function fetchFuzzy(
        Request $request,
        Builder $query,
        Stringable $familyName,
        Collection $neighbourCentreIds,
        bool $descending,
    ): LengthAwarePaginator {
        $rankedFamilyIds = collect(
            Searchy::search('carers')
                ->fields('name')
                ->query((string)$familyName)
                ->get()
        )->pluck('family_id')->toArray();

        $permittedFamilyIds = Registration::query()
            ->whereIn('family_id', $rankedFamilyIds)
            ->whereIn('centre_id', $neighbourCentreIds)
            ->pluck('family_id')
            ->toArray();

        $familyIds = collect($rankedFamilyIds)
            ->filter(function (int $id) use ($permittedFamilyIds) {
                return in_array($id, $permittedFamilyIds, strict: true);
            })
            ->values()
            ->toArray();

        $positionMap = array_flip($familyIds);

        $all = $query
            ->whereIn('registrations.family_id', $familyIds)
            ->WithFullFamily()
            ->get()
            ->sortBy(
                function ($reg) use ($positionMap) {
                    return $positionMap[$reg->family_id] ?? PHP_INT_MAX;
                },
                SORT_REGULAR,
                $descending,
            )
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            items: $all->forPage($page, 10),
            total: $all->count(),
            perPage: 10,
            currentPage: $page,
            options: [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->except('page'),
            ]
        );
    }

    /**
     * Returns the registration page
     *
     * @return View|Factory|Application
     */
    public function create(): View|Factory|Application
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if we verify, based on the currently logged in user's context
        // as we have no registration to refer to yet.
        $evaluator = EvaluatorFactory::make($user->centre->sponsor->evaluations);
        $sponsorsRequiresID = $evaluator->isVerifyingChildren();

        // did we reload with some child data that needs fixing?
        if (old('children')) {
            $children = $this->makeChildrenFromInput(
                (array)old('children')
            );
        }

        $data = [
            "user_name" => $user->name,
            "centre_name" => $user->centre?->name,
            "sponsorsRequiresID" => $sponsorsRequiresID,
            "programme" => $user->centre->sponsor->programme,
            'leaver' => false,
            'children' => $children ?? [],
        ];
        return view('store.create_registration', $data);
    }

    /**
     * Makes children from input data
     * @param array $children
     * @return array
     */
    private function makeChildrenFromInput(array $children = []): array
    {
        return Arr::map(
            $children,
            static function ($child): Child {
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
            }
        );
    }

    /**
     * Show the Registration / Family edit form
     *
     * @param Registration $registration
     * @return View|Factory|Application
     */
    public function edit(Registration $registration): View|Factory|Application
    {
        // Get User and Centre;
        /** @var CentreUser $user */
        $user = Auth::user();
        $data = [
            'user_name' => $user->name,
            'centre_name' => $user->centre?->name,
            'programme' => $user->centre?->sponsor->programme,
        ];

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        $registration = Registration::withFullFamily()->find($registration->id);

        $evaluations = $registration->centre->sponsor->evaluations;
        $deferrable = $evaluations->contains('name', 'ScottishChildCanDefer');

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Grab carers copy for shifting without altering family->carers
        $carers = $registration->family->carers->all();
        $pri_carer = array_shift($carers);
        $pri_carer_ethnicity = $pri_carer->ethnicity;
        $pri_carer_language = $pri_carer->language;

        $evaluations["creditables"] = $registration->getEvaluator()->getPurposeFilteredEvaluations("credits");
        $evaluations["disqualifiers"] = $registration->getEvaluator()->getPurposeFilteredEvaluations("disqualifiers");

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
                'can_change_defer' => Carbon::now()->month <= config('arc.scottish_school_month'),
                'leaver' => false,
            ]
        ));
    }

    /**
     * Show the Registration / Family view form for a leaver
     *
     * @param Registration $registration
     * @return View|Factory|Application
     */
    public function view(Registration $registration): View|Factory|Application
    {
        // Get User and Centre;
        /** @var CentreUser $user */
        $user = Auth::user();
        $data = [
            'user_name' => $user->name,
            'centre_name' => $user->centre?->name,
            'programme' => $user->centre?->sponsor->programme,
        ];

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        $registration = Registration::withFullFamily()->find($registration->id);

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Grab carers copy for shift)ing without altering family->carers
        $carers = $registration->family->carers->all();

        return view('store.view_registration', array_merge(
            $data,
            [
                'registration' => $registration,
                'family' => $registration->family,
                'pri_carer' => array_shift($carers),
                'children' => $registration->family->children,
                'entitlement' => $valuation->getEntitlement(),
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
    public function printOneIndividualFamilyForm(Registration $registration): Response
    {
        // Get User
        $user = Auth::user();

        // Get the registration, with deep eager-loaded Family (with Children and Carers)
        $registration = Registration::withFullFamily()->find($registration->id);

        // Get the valuation
        /** @var Valuation $valuation */
        $valuation = $registration->getValuation();

        // Make a filename
        $filename = 'Registration' . Carbon::now()->format('YmdHis') . '.pdf';

        // Setup common data
        $data = [
            'user_name' => $user->name,
            'centre_name' => $user->centre?->name,
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
        $centre = $user?->centre;

        // Cope if User has no Centre.
        if (!$centre) {
            Log::info("User $user?->id has no Centre");
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
            ->sortBy('family.pri_carer', SORT_NATURAL);

        if (empty($registrations)) {
            return redirect()
                ->route('store.dashboard')
                ->with('error_message', 'No Registrations in that centre.');
        }

        // Make a filename
        $filename = 'Registrations_' . Carbon::now()->format('YmdHis') . '.pdf';

        // Set up the common view data.
        $data = [
            'user_name' => $user?->name,
            'centre_name' => $user?->centre?->name,
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
     */
    public function store(StoreNewRegistrationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $carers = array_merge(
            array_map(static function (string $name) use ($data): Carer {
                return new Carer([
                    'name' => $name,
                    'ethnicity' => $data['pri_carer_ethnicity'] ?? null,
                    'language' => $data['pri_carer_language'] ?? null,
                    'telnosecret' => $data['pri_carer_telno'] ?? null,
                    'emailsecret' => $data['pri_carer_email'] ?? null,
                ]);
            }, (array)($data['pri_carer'] ?? [])),
            array_map(
                static function (string $name): Carer {
                    return new Carer(['name' => $name]);
                },
                (array)($data['new_carers'] ?? [])
            )
        );

        $children = $this->makeChildrenFromInput((array)($data['children'] ?? []));

        $registration = new Registration([
            'consented_on' => Carbon::now(),
            'eligibility_hsbs' => $data['eligibility-hsbs'] ?? null,
            'eligibility_nrpf' => $data['eligibility-nrpf'] ?? null,
            'eligible_from' => ($data['eligibility-hsbs'] ?? null) === 'healthy-start-receiving' ? Carbon::now() : null,
        ]);

        $family = new Family();
        $family->lockToCentre(Auth::user()->centre);

        try {
            DB::transaction(callback: static function () use ($registration, $family, $carers, $children): void {
                $family->save();
                $family->carers()->saveMany($carers);
                $family->children()->saveMany($children);
                $registration->family()->associate($family);
                $registration->centre()->associate(Auth::user()->centre);
                $registration->save();
            });
        } catch (Throwable $e) {
            Log::error(sprintf('Bad transaction for %s@%s by service user %s', __CLASS__, __METHOD__, Auth::id()));
            Log::error($e->getTraceAsString());

            return redirect()->route('store.registration.create')->withErrors('Registration failed.');
        }

        Log::info(sprintf('Registration %s created by service user %s', $registration->id, Auth::id()));

        return redirect()
            ->route('store.registration.edit', $registration)
            ->with('message', 'Registration created.');
    }

    /**
     * Update a Registration
     */
    public function update(StoreUpdateRegistrationRequest $request, Registration $registration): RedirectResponse
    {
        $data = $request->validated();
        $amendedCarers = [];

        // Primary carer
        $priCarerInput = (array)($data['pri_carer'] ?? []);
        $priCarerId = (int)array_key_first($priCarerInput);
        $priCarer = Carer::findOrFail($priCarerId);

        if ($priCarer->name !== $priCarerInput[$priCarerId]) {
            $priCarer->name = $priCarerInput[$priCarerId];
            $amendedCarers[] = $priCarer;
        }

        $priEthnicity = $data['pri_carer_ethnicity'][$priCarerId] ?? null;
        if ($priEthnicity !== null && $priCarer->ethnicity !== $priEthnicity) {
            $priCarer->ethnicity = $priEthnicity;
            $amendedCarers[] = $priCarer;
        }

        $priLanguage = $data['pri_carer_language'][$priCarerId] ?? null;
        if ($priLanguage !== null && $priCarer->language !== $priLanguage) {
            $priCarer->language = $priLanguage;
            $amendedCarers[] = $priCarer;
        }

        // emailsecret and telnosecret are special
        $priEmail = $data['pri_carer_email'][$priCarerId] ?? null;
        if ($priEmail !== null && $priCarer->emailsecret->reveal() !== $priEmail) {
            $priCarer->emailsecret = $priEmail;
            $amendedCarers[] = $priCarer;
        }

        $priTelno = $data['pri_carer_telno'][$priCarerId] ?? null;
        if ($priTelno !== null && $priCarer->telnosecret->reveal() !== $priTelno) {
            $priCarer->telnosecret = $priTelno;
            $amendedCarers[] = $priCarer;
        }

        // Secondary carers — diff DB state against input to find stale IDs
        $secCarersInput = (array)($data['sec_carers'] ?? []);
        $staleCarerIds = $registration->family->carers
            ->pluck('id')
            ->reject(function (int $id) use ($priCarerId): bool {
                return $id === $priCarerId;
            })
            ->diff(array_keys($secCarersInput))
            ->values()
            ->all();

        foreach (Carer::whereIn('id', array_keys($secCarersInput))->get() as $carer) {
            if ($carer->name !== $secCarersInput[$carer->id]) {
                $carer->name = $secCarersInput[$carer->id];
                $amendedCarers[] = $carer;
            }
        }

        // New carers and children
        $newCarers = array_map(
            static function (string $name): Carer {
                return new Carer(['name' => $name]);
            },
            (array)($data['new_carers'] ?? [])
        );

        $children = $this->makeChildrenFromInput((array)($data['children'] ?? []));

        $eligibleFrom = (
            ($data['eligibility-hsbs'] ?? null) === 'healthy-start-receiving' &&
            !$registration->eligible_from
        )
            ? Carbon::now()
            : null;

        try {
            DB::transaction(static function () use (
                $registration,
                $amendedCarers,
                $newCarers,
                $staleCarerIds,
                $children,
                $data,
                $eligibleFrom,
            ): void {
                $family = $registration->family;

                Carer::whereIn('id', $staleCarerIds)->delete();
                $family->carers()->saveMany($newCarers);

                $family->children()->delete();
                $family->children()->saveMany($children);

                collect($amendedCarers)->unique()->each(function (Carer $carer) {
                    return $carer->save();
                });

                $registration->fill([
                    'eligibility_hsbs' => $data['eligibility-hsbs'] ?? null,
                    'eligibility_nrpf' => $data['eligibility-nrpf'] ?? null,
                    'eligible_from' => $eligibleFrom,
                ])->save();
            });
        } catch (Throwable $e) {
            Log::error(sprintf('Bad transaction for %s@%s by service user %s', __CLASS__, __METHOD__, Auth::id()));
            Log::error($e->getTraceAsString());

            return redirect()
                ->route('store.registration.edit', $registration)
                ->withErrors('Registration update failed.');
        }

        Log::info(sprintf('Registration %s updated by service user %s', $registration->id, Auth::id()));

        return redirect()
            ->route('store.registration.edit', $registration)
            ->with('message', 'Registration updated.');
    }
}
