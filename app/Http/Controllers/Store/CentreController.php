<?php

namespace App\Http\Controllers\Store;

use App\Centre;
use App\CentreUser;
use App\Http\Controllers\Controller;
use App\Registration;
use App\Services\VoucherEvaluator\Valuation;
use Auth;
use Carbon\Carbon;
use Excel;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use PDF;

class CentreController extends Controller
{
    /**
     * Displays a printable version of the families registered with the center.
     *
     * @param Centre $centre
     * @return Response
     */
    public function printCentreCollectionForm(Centre $centre)
    {
        $registrations = $centre->registrations()
            ->whereActiveFamily()
            ->withFullFamily()
            ->get()
            ->sortBy(function ($registration) {
                // Need strtolower because case comparison sucks.
                return strtolower($registration->family->pri_carer);
            });

        $filename = 'CC' . $centre->id . 'Regs_' . Carbon::now()->format('YmdHis') . '.pdf';

        $pdf = PDF::loadView(
            'store.printables.families',
            [
                'sheet_title' => 'Printable Register',
                'sheet_header' => 'Register',
                'centre' => $centre,
                'registrations' => $registrations,
            ]
        );
        $pdf->setPaper('A4', 'landscape');

        return @$pdf->download($filename);
    }

    /**
     * Exports a summary of registrations from the User's relevant Centres or specified Centre.
     *
     * @param Centre $centre
     * @return ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function exportRegistrationsSummary(Centre $centre)
    {
        // Get User
        /** @var CentreUser $user */
        $user = Auth::user();

        // The "export" policy can export all the things
        if (!$user->can('export', CentreUser::class)) {
            // Set for specified centre
            $centre_ids = [$centre->id];
            $dateFormats = [
                'dob' => 'm/Y'
            ];
            $excludeColumns = [
                'Active',
                'Date file was Downloaded',
            ];
        } else {
            // Set for relevant centres
            $centre_ids = $user->relevantCentres()->pluck('id')->all();
            $dateFormats = [];
            $excludeColumns = [];
        }

        $summary = $this->getCentreRegistrationsSummary($centre_ids, $dateFormats, $excludeColumns);

        list($rows, $headers) = $summary;

        if (count($headers) < 1 || count($rows) < 1) {
            return redirect()
                ->route('store.dashboard')
                ->with('error_message', 'No Registrations in that centre.');
        }

        return $this->streamFile(
            $this->writeExcelDoc($summary)
        );
    }

    /**
     * Returns array of formatted data about the Centre's Registrations
     *
     * @param array $centre_ids
     * @param array $dateFormats
     * @param array $excludeColumns
     * @return array
     */
    private function getCentreRegistrationsSummary(array $centre_ids, $dateFormats = [], $excludeColumns = [])
    {
        $dateFormats = array_replace([
            'lastCollection' => 'd/m/Y',
            'due' => 'd/m/Y',
            'dob' => 'd/m/Y',
            'join' => 'd/m/Y',
            'leave' => 'd/m/Y'
        ], $dateFormats);

        // Get registrations decorated - may no longer be terribly efficient.
        /** @var Collection $registrations */
        $registrations = Registration::withFullFamily()
            ->whereIn(
                'centre_id',
                $centre_ids
            )
            ->with(['centre', 'centre.sponsor'])
            ->get();

        // set blank rows for laravel-excel
        $rows = [];

        // Looks like Laravel-PHPExcel can't auto-generate headers
        // by collating all the row keys and normalising.
        // So we have to do it by hand.

        // Initialise base headers
        $headers = [];

        // Per registration...
        foreach ($registrations as $reg) {
            $lastCollection = $reg->bundles()
                ->whereNotNull('disbursed_at')
                ->orderBy('disbursed_at', 'desc')
                ->first();

            $lastCollectionDate = $lastCollection
                ? $lastCollection->disbursed_at
                : null;

            // Null coalesce `??` does not trigger `Trying to get property of non-object` explosions
            $row = [
                'RVID' => ($reg->family->rvid) ?? 'Family not found',
                'Area' => ($reg->centre->sponsor->name) ?? 'Area not found',
                'Centre' => ($reg->centre->name) ?? 'Centre not found',
                'Primary Carer' => ($reg->family->pri_carer) ?? 'Primary Carer not Found',
                'Entitlement' => $reg->getValuation()->getEntitlement(),
                'Last Collection' => (!is_null($lastCollectionDate)) ? $lastCollectionDate->format($dateFormats['lastCollection']) : null,
                'Active' => ($reg->isActive()) ? 'true' : 'false'
            ];

            // Per child dependent things
            $kids = [];
            $due_date = null;
            $eligibleKids = 0;

            // Evaluate it.
            $regValuation = $reg->valuatation;

            if ($reg->family) {
                /** @var Valuation $familyValuation */
                $familyValuation = $reg->family->getValuation();
                $child_index = 1;
                foreach ($reg->family->children as $child) {
                    // Will run a child valuation if we don't already have one.
                    /** @var Valuation $childValuation */
                    $childValuation = $child->getValuation();

                    if ($child->dob->isFuture()) {
                        // If it's a pregnancy, set due date and move on.
                        $due_date = $child->dob->format($dateFormats['dob']);
                    } else {
                        // Otherwise, set the header
                        $dob_header = 'Child ' . (string)$child_index . ' DoB';
                        $kids[$dob_header] = $child->dob->lastOfMonth()->format($dateFormats['dob']);
                        $child_index += 1;
                        // A child is eligible if it's family is AND it has no disqualifications of it's own.
                        if ($familyValuation->getEligibility() && $childValuation->getEligibility()) {
                            $eligibleKids += 1;
                        }
                    }
                }
            }
            // Add count of eligible kids
            $row['Eligible Children'] = $eligibleKids;

            // Add our kids back in
            $row = array_merge($row, $kids);

            // Set the last dates.
            $row['Due Date'] = $due_date;
            $row['Join Date'] = $reg->created_at ? $reg->created_at->format($dateFormats['join']) : null;
            $row['Leaving Date'] = $reg->family->leaving_on ? $reg->family->leaving_on->format($dateFormats['leave']) : null;
            // Would be confusing if an old reason was left in - so check leaving date is there.
            $row['Leaving Reason'] = $reg->family->leaving_on ? $reg->family->leaving_reason : null;

            $row['Date file was Downloaded'] = Carbon::today()->toDateString();

            // Remove any keys we don't want
            foreach ($excludeColumns as $excludeColumn) {
                unset($row[$excludeColumn]);
            }

            // Update the headers if necessary...
            if (count($headers) < count($row)) {
                $headers = array_keys($row);
            }

            // And add to the list.
            $rows[] = $row;
        }

        // Sort the columns
        usort($rows, function ($a, $b) use ($dateFormats) {
            // If we haven't ever collected, with unix epoch start (far past)
            $aActiveDate = ($a['Last Collection'])
                ? Carbon::createFromFormat($dateFormats['lastCollection'], $a['Last Collection'])
                : Carbon::parse('1970-01-01');

            $bActiveDate = ($b['Last Collection'])
                ? Carbon::createFromFormat($dateFormats['lastCollection'], $b['Last Collection'])
                : Carbon::parse('1970-01-01');

            $hashA = strtolower(
                $a['Area'] . '#' .
                $a['Centre'] . '#' .
                $aActiveDate->toDateString() . '#' .
                $a['Primary Carer']
            );
            $hashB = strtolower(
                $b['Area'] . '#' .
                $b['Centre'] . '#' .
                $bActiveDate->toDateString() . '#' .
                $b['Primary Carer']
            );
            // PHP 7 feature; comparison "spaceship" opertator "<=>" : returns -1/0/1
            return $hashA <=> $hashB;
        });

        // en-sparsen the rows with empty fields for unused header.
        foreach ($rows as $index => $row) {
            $sparse_row = [];
            foreach ($headers as $header) {
                $sparse_row[$header] = (array_key_exists($header, $row)) ? $row[$header] : null;
            }
            // Key/value order matters to laravel-excel
            $rows[$index] = $sparse_row;
        }

        return [$rows, $headers];
    }

    /**
     * Returns a configured writer for the file
     * @param array $summary
     * @return LaravelExcelWriter
     */
    private function writeExcelDoc(array $summary)
    {
        // Get user
        /** @var CentreUser $user */
        $user = Auth::user();

        // Destructure summary
        list($rows, $headers) = $summary;

        // Get now()
        $now = Carbon::now();

        /** @var LaravelExcelWriter $excel_doc */
        $excel_doc = Excel::create(
            'RegSummary_' . $now->format('YmdHis'),
            function ($excel) use ($user, $rows, $headers) {
                $excel->setTitle('Registration Summary');
                $excel->setDescription('Summary of Registrations from Centres available to ' . $user->name);
                $excel->setManager($user->name);
                $excel->setCompany(env('APP_URL'));
                $excel->setCreator(env('APP_NAME'));
                $excel->setKeywords([]);
                $excel->sheet(
                    'Registrations',
                    function ($sheet) use ($rows, $headers) {
                        $sheet->setOrientation('landscape');
                        $sheet->row(1, $headers);
                        $sheet->cells('A1', function ($cells) {
                            $cells->setBackground('#6495ED')
                                ->setFontWeight('bold');
                        });
                        $letters = range('A', 'Z');
                        $sheet->cells('B1:' . $letters[count($headers) - 1] . '1', function ($cells) {
                            $cells->setBackground('#9ACD32')
                                ->setFontWeight('bold');
                        });
                        $sheet->fromArray($rows, null, 'A2', false, false);
                    }
                );
            }
        );
        return $excel_doc;
    }

    /**
     * Writes and returns file to client
     * @param LaravelExcelWriter $excel_doc
     * @return ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    private function streamFile(LaravelExcelWriter $excel_doc)
    {
        // This appears to help with a PHPUnit/Laravel-excel file download issue.
        $excel_ident = app('excel.identifier');
        $format = $excel_ident->getFormatByExtension('csv');
        $contentType = $excel_ident->getContentTypeByFormat($format);

        try {
            return response($excel_doc->string('csv'), 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="' . $excel_doc->getFileName() . '.csv"',
                'Expires' => Carbon::createFromTimestamp(0)->format('D, d M Y H:i:s'),
                'Last-Modified' => Carbon::now()->format('D, d M Y H:i:s'),
                'Cache-Control' => 'cache, must-revalidate',
                'Pragma' => 'public',
            ]);
        } catch (LaravelExcelException $e) {
            abort(500, 'Unable to create document');
        }
    }
}
