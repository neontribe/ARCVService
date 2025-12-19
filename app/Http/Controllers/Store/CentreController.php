<?php

namespace App\Http\Controllers\Store;

use App\Centre;
use App\CentreUser;
use App\Http\Controllers\Controller;
use App\Registration;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PDF;

class CentreController extends Controller
{
    protected array $defaultDateFormats = [
        'lastCollection' => 'd/m/Y',
        'due' => 'd/m/Y',
        'dob' => 'd/m/Y',
        'join' => 'd/m/Y',
        'leave' => 'd/m/Y',
        'eligible_from' => 'd/m/Y',
        'rejoin' => 'd/m/Y',
    ];

    protected array $labels = [
        'family' => [
            'carer' => 'Primary Carer',
            'rvid' => 'Family',
            'dob_header' => 'Child %d DoB',
        ],
        'sp' => [
            'carer' => 'Main Participant',
            'rvid' => 'Household',
            'dob_header' => 'Household member %d DoB',
        ],
    ];

    // Default date formats for both files
    protected array $dateFormats;

    // Different labels for family vs SP programmes
    protected array $excludeColumns;
    protected int $programme;
    protected string $labelType;

    /**
     * Displays a printable version of the families registered with the center.
     */
    public function printCentreCollectionForm(Centre $centre): Response
    {
        $registrations = $centre->registrations()->whereActiveFamily()->withFullFamily()->get()->sortBy(function (
            $registration
        ): string {
            // Need strtolower because case comparison sucks.
            return strtolower($registration->family->pri_carer);
        });

        $filename = 'CC' . $centre->id . 'Regs_' . Carbon::now()->format('YmdHis') . '.pdf';

        $programme = $centre->sponsor->programme;
        $pdf_route = $programme ? 'store.printables.household' : 'store.printables.families';
        $pdf = PDF::loadView($pdf_route, [
            'sheet_title' => 'Printable Register',
            'sheet_header' => 'Register',
            'centre' => $centre,
            'registrations' => $registrations,
        ]);
        $pdf->setPaper('A4', 'landscape');

        return @$pdf->download($filename);
    }

    /**
     * Exports a summary of registrations from the User's relevant Centres or specified Centre.
     */
    public function exportRegistrationsSummary(
        Request $request,
        Centre $centre
    ): Application|Response|RedirectResponse|ResponseFactory {
        $programme = (int)$request->input('programme', 0);

        // Get User
        /** @var CentreUser $user */
        $user = Auth::user();

        // The "export" policy can export all the things
        if (!$user->can('export', CentreUser::class)) {
            // Set for specified centre
            $centre_ids = [$centre->id];
            $dateFormats = [
                'dob' => 'm/Y',
            ];
            $excludeColumns = [
                'Active',
                'Date file was Downloaded',
            ];
        } else {
            // Set for relevant centres
            $centre_ids = $user->relevantCentres($programme)->pluck('id')->all();
            $dateFormats = [];
            $excludeColumns = [];
        }

        $this->dateFormats = array_merge($this->defaultDateFormats, $dateFormats);
        $this->excludeColumns = $excludeColumns;
        $this->programme = $programme;
        $this->labelType = $programme ? 'sp' : 'family';


        [$rows, $headers] = $this->generate($centre_ids);

        if (count($headers) < 1 || count($rows) < 1) {
            return redirect()->route('store.dashboard')->with('error_message', 'No Registrations in that centre.');
        }

        $tmp = fopen('php://temp', 'r+');
        fputcsv($tmp, $headers);
        foreach ($rows as $row) {
            fputcsv($tmp, $row);
        }
        rewind($tmp);
        $csv = stream_get_contents($tmp);
        fclose($tmp);

        return response($csv, 200, [
            'Content-Type' => "text/csv",
            'Content-Disposition' => 'attachment; filename="RegSummary_' . Carbon::now()->format('YmdHis') . '.csv"',
            'Expires' => Carbon::createFromTimestamp(0)->format('D, d M Y H:i:s'),
            'Last-Modified' => Carbon::now()->format('D, d M Y H:i:s'),
            'Cache-Control' => 'cache, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    // Generate summary rows and headers based on centreIDs

    public function generate(array $centreIds): array
    {
        $registrations = $this->getRegistrations($centreIds);
        $rows = [];
        $headers = [];

        foreach ($registrations as $registration) {
            $row = $this->buildBaseRow($registration);
            $childrenData = $this->processChildren($registration);

            $fullRow = array_merge(
                $row,
                $childrenData['child_rows'],
                $this->addProgrammeSpecificFields($registration, $childrenData)
            );
            // Remove excluded columns
            foreach ($this->excludeColumns as $column) {
                unset($fullRow[$column]);
            }

            if (count($headers) < count($fullRow)) {
                $headers = array_keys($fullRow);
            }

            $rows[] = $fullRow;
        }

        return $this->sortRows($rows, $headers);
    }

    // methods used by both report types

    protected function getRegistrations(array $centreIds): Collection
    {
        return Registration::withFullFamily()->whereIn('centre_id', $centreIds)->with([
            'centre',
            'centre.sponsor',
        ])->get();
    }

    protected function buildBaseRow(Registration $registration): array
    {
        $lastCollection = $this->getLastCollectionDate($registration);
        $label = $this->labels[$this->labelType];

        return [
            'RVID' => $registration->family->rvid ?? "{$label['rvid']} not found",
            'Area' => $registration->centre->sponsor->name ?? 'Area not found',
            'Centre' => $registration->centre->name ?? 'Centre not found',
            $label['carer'] => $registration->family->pri_carer ?? "{$label['carer']} not found",
            'Entitlement' => $registration->getValuation()->getEntitlement(),
            'Last Collection' => $lastCollection ? $lastCollection->format($this->dateFormats['lastCollection']) : null,
            'Active' => $registration->isActive() ? 'true' : 'false',
        ];
    }

    protected function getLastCollectionDate(Registration $registration)
    {
        $lastCollection = $registration->bundles()->whereNotNull('disbursed_at')->orderBy(
            'disbursed_at',
            'desc'
        )->first();
        return $lastCollection?->disbursed_at;
    }

    protected function processChildren(Registration $registration): array
    {
        $result = [
            'child_rows' => [],
            'due_date' => null,
            'eligible_count' => 0,
            'total_count' => 0,
        ];

        if (!$registration->family) {
            return $result;
        }

        $familyValuation = $registration->family->getValuation();
        $childIndex = 1;

        if ($this->programme === 1) {
            $pri_carer = $registration->family->children->firstWhere('is_pri_carer', 1);
            if (!empty($pri_carer)) {
                $result['child_rows']['Main Participant DoB'] = $pri_carer
                    ->dob
                    ->lastOfMonth()
                    ->format($this->dateFormats['dob']);
            }
        }

        foreach ($registration->family->children as $child) {
            if ($this->programme && $child->is_pri_carer) {
                continue;
            }

            if ($child->dob->isFuture()) {
                $result['due_date'] = $child->dob->format($this->dateFormats['dob']);
                continue;
            }

            $header = sprintf($this->labels[$this->labelType]['dob_header'], $childIndex);
            $result['child_rows'][$header] = $child->dob->lastOfMonth()->format($this->dateFormats['dob']);
            $childIndex++;

            if ($familyValuation->getEligibility() && $child->getValuation()->getEligibility()) {
                $result['eligible_count']++;
            }

            $result['total_count']++;
        }

        return $result;
    }

    protected function addProgrammeSpecificFields(Registration $registration, array $childrenData): array
    {
        $family = $registration->family;

        $fields = [
            'Join Date' => $registration->created_at->format($this->dateFormats['join']),
            'Leaving Date' => $family->leaving_on?->format($this->dateFormats['leave']),
            'Leaving Reason' => $family->leaving_reason ?? null,
            'Rejoin Date' => $family->rejoin_on?->format($this->dateFormats['rejoin']),
            'Days on programme' => $this->calculateDaysOnProgramme($registration),
            'Leave Count' => $family->leave_amount ?? null,
        ];

        switch ($this->programme) {
            case 0: // Family mode
                $fields = [
                    ...$fields,
                    'Due Date' => $childrenData['due_date'],
                    'Total Children' => $childrenData['total_count'],
                    'Eligible Children' => $childrenData['eligible_count'],
                    'Family Eligibility (HSBS)' => $registration->eligibility_hsbs ?? null,
                    'Family Eligibility (NRPF)' => ucfirst($registration->eligibility_nrpf ?? ''),
                    'Eligible From' => $registration->eligible_from?->format($this->dateFormats['eligible_from']),
                    ...$this->getCarerDetails($registration),
                ];
                break;
            case 1: // SP mode
                $fields = [
                    ...$fields,
                    'Eligible Household Members' => $childrenData['eligible_count'],
                    ...$this->getCarerDetails($registration),
                ];
                break;
            default:
                break;
        }

        if (!in_array('Date file was Downloaded', $this->excludeColumns, true)) {
            $fields['Date file was Downloaded'] = Carbon::today()->toDateString();
        }

        return $fields;
    }

    protected function calculateDaysOnProgramme(Registration $registration): ?int
    {
        $family = $registration->family;

        $startDate = $registration->created_at;
        $leaveDate = $family->leaving_on;
        $rejoinDate = $family->rejoin_on;

        if ($leaveDate && !$rejoinDate) {
            return $startDate->diffInDays($leaveDate);
        }

        if ($rejoinDate) {
            return $startDate->diffInDays($leaveDate) + $rejoinDate->diffInDays(now());
        }
        return null;
    }

    protected function getCarerDetails(Registration $registration): array
    {
        $carer = $registration->family->carers()->first(['ethnicity', 'language']);

        $language = 'not answered';
        $otherLanguage = '';

        if ($carer && ($carer->language !== null)) {
            $language = ($carer->language === 'english') ? 'english' : 'other';
            $otherLanguage = ($language === 'other') ? strtolower($carer->language) : '';
        }

        return [
            'Ethnicity' => config('arc.ethnicity_desc.' . ($carer->ethnicity ?? '')),
            'Main Language' => $language,
            'Other Language' => $otherLanguage,
        ];
    }

    protected function sortRows(array $rows, array $headers): array
    {
        $carerKey = $this->labels[$this->labelType]['carer'];
        $dateFormat = $this->dateFormats['lastCollection'];

        $parseDate = static function (?string $date) use ($dateFormat) {
            try {
                return $date ? Carbon::createFromFormat($dateFormat, $date) : Carbon::create(1970, 1, 1);
            } catch (Exception $e) {
                return Carbon::create(1970, 1, 1);
            }
        };

        usort($rows, static function ($a, $b) use ($parseDate, $carerKey) {
            $aDate = $parseDate($a['Last Collection']);
            $bDate = $parseDate($b['Last Collection']);

            $aHash = strtolower(implode('#', [
                $a['Area'] ?? '',
                $a['Centre'] ?? '',
                $aDate->toDateString(),
                $a[$carerKey] ?? '',
            ]));

            $bHash = strtolower(implode('#', [
                $b['Area'] ?? '',
                $b['Centre'] ?? '',
                $bDate->toDateString(),
                $b[$carerKey] ?? '',
            ]));

            return $aHash <=> $bHash;
        });

        $rows = array_map(static function ($row) use ($headers) {
            return [...array_fill_keys($headers, null), ...$row];
        }, $rows);

        return [$rows, $headers];
    }
}
