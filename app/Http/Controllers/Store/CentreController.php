<?php

namespace App\Http\Controllers\Service;

use App\Centre;
use App\Http\Controllers\Controller;
use App\Registration;
use Auth;
use Carbon\Carbon;
use Excel;
use Illuminate\View\View;
use PDF;

class CentreController extends Controller
{

    /**
     * Displays a printable version of the families registered with the center.
     *
     * @param Centre $centre
     * @return \Illuminate\Contracts\View\Factory|View
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

        $filename = 'CC' . $centre->id . 'Regs_' . Carbon::now()->format('YmdHis') .'.pdf';

        $pdf = PDF::loadView(
            'service.printables.families',
            [
                'sheet_title' => 'Printable Register',
                'sheet_header' => 'Register',
                'centre' => $centre,
                //'reg_chunks' => $reg_chunks,
                'registrations' => $registrations,
            ]
        );
        $pdf->setPaper('A4', 'landscape');

        return @$pdf->download($filename);
    }

    /**
     * Exports a summary of registrations from the User's relevant Centres.
     *
     */
    public function exportRegistrationsSummary()
    {
        // Get User
        $user = Auth::user();

        // Get now()
        $now = Carbon::now();

        // Get centres
        $centres = $user->relevantCentres();

        // get registrations
        $registrations = Registration::whereIn('centre_id', $centres->pluck('id')->all())
            ->with(['centre','family.children','family.carers'])
            ->get();

        // set blank rows for laravel-excel
        $rows = [];

        // Looks like larevel-excel can't auto-generate headers
        // by collating all the row keys and normalising.
        // So we have to do it by hand.

        // create base headers
        $headers = [
        ];

        // Per registration...
        foreach ($registrations as $reg) {
            $row = [
                // TODO: null objects when DB is duff: try/catch findOrFail() in the relationship?
                "RVID" => ($reg->family) ? $reg->family->rvid : 'Family not found',
                "Centre" => ($reg->centre) ? $reg->centre->name : 'Centre not found',
                "Primary Carer" => ($reg->family->carers->first()) ? $reg->family->carers->first()->name : null,
                "Food Chart Received" => (!is_null($reg->fm_chart_on)) ? $reg->fm_chart_on->format('d/m/Y') : null,
                "Food Diary Received" => (!is_null($reg->fm_diary_on)) ? $reg->fm_diary_on->format('d/m/Y') : null,
                "Privacy Statement Received" => (!is_null($reg->fm_privacy_on)) ? $reg->fm_privacy_on->format('d/m/Y') : null,
                "Entitlement" => $reg->family->entitlement,
            ];

            // Per child dependent things
            $kids = [];
            $due_date = null;
            $eligible = 0;

            if ($reg->family) {
                $child_index = 0;
                foreach ($reg->family->children as $child) {
                    // make a 'Child X DoB' key
                    $status = $child->getStatus();

                    // Arrange kids by eligibility
                    switch ($status['eligibility']) {
                        case 'Pregnancy':
                            $due_date = $child->dob->format('d/m/Y');
                            break;
                        case 'Eligible':
                            $dob_header = 'Child ' . (string)$child_index . ' DoB';
                            $kids[$dob_header] = $child->dob->format('m/Y');
                            $eligible += 1;
                            $child_index += 1;
                            break;
                        case "Ineligible":
                            $dob_header = 'Child ' . (string)$child_index . ' DoB';
                            $kids[$dob_header] = $child->dob->format('m/Y');
                            $child_index += 1;
                            break;
                    }
                }
            }
            // Add count of eligible kids
            $row["Eligible Children"] = $eligible;

            // Add our kids back in
            $row = array_merge($row, $kids);

            // Set the last dates.
            $row["Due Date"] = $due_date;
            $row["Join Date"] = $reg->family->created_at ? $reg->family->created_at->format('d/m/Y')  : null;
            $row["Leaving Date"] = $reg->family->leaving_on ? $reg->family->leaving_on->format('d/m/Y') : null;
            // Would be confusing if an old reason was left in - so check leaving date is there.
            $row["Leaving Reason"] = $reg->family->leaving_on ? $reg->family->leaving_reason : null;

            // update the headers if necessary
            if (count($headers) < count($row)) {
                $headers = array_keys($row);
            }
            // stack new row onto the array
            $rows[] = $row;
        }
        
        // PHP 7 feature; comparison "spaceship" opertator "<=>" : returns -1/0/1 
        usort($rows, function ($a, $b) {
            return $a['RVID'] <=> $b['RVID'];
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

        /**
         * TODO: write an OO system for formatting things better.
         * Ideally we'd have formatting for
         * - rows with a leaving date showing grey
         * - ineligible children showing grey
         * - children with changes in near future showing red.
         */
        $excel_doc = Excel::create(
            'RegSummary_' . $now->format('YmdHis'),
            function ($excel) use ($user, $rows, $headers) {
                $excel->setTitle('Registration Summary');
                $excel->setDescription('Summary of Registrations from Centres available to '. $user->name);
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
                        $sheet->cells('B1:' . $letters[count($headers)-1] .'1', function ($cells) {
                            $cells->setBackground('#9ACD32')
                                ->setFontWeight('bold');
                        });
                        $sheet->fromArray($rows, null, 'A2', false, false);
                    }
                );
            }
        );

        // This appears to help with a PHPUnit/Laravel-excel file download issue.
        $excel_ident = app('excel.identifier');
        $format = $excel_ident->getFormatByExtension('csv');
        $contentType = $excel_ident->getContentTypeByFormat($format);

        return response($excel_doc->string('csv'), 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $excel_doc->getFileName() . '.csv"',
            'Expires' => Carbon::createFromTimestamp(0)->format('D, d M Y H:i:s'),
            'Last-Modified' => Carbon::now()->format('D, d M Y H:i:s'),
            'Cache-Control' => 'cache, must-revalidate',
            'Pragma' => 'public',
        ]);

        // avoid xls till we have all the formatting.
        //)->download('xls');
    }
}
