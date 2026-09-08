<?php

namespace App\Http\Controllers\Service\Data;

use App\Carer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FamilyContactsController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $now = Carbon::now()->format('YmdHis');
        return response()->streamDownload(function () {

            $output = fopen('php://output', 'wb');

            fputcsv($output, ['Rvid', 'Name', 'Email', 'Telno', 'Centre', 'Area']);

            $chunkSize = 200;
            $counter = 0;
            Carer::query()
                ->where(function ($query) {
                    $query->whereNotNull('emailsecret')
                        ->orWhereNotNull('telnosecret');
                })
                ->with(['family.initialCentre.sponsor'])
                ->lazyById($chunkSize)
                ->each(function (Carer $carer) use ($chunkSize, $output, &$counter) {
                    fputcsv($output, [
                        $carer->family?->Rvid,
                        $carer->name,
                        $carer->emailsecret->reveal(),
                        $carer->telnosecret->reveal(),
                        $carer->family?->initialCentre?->name,
                        $carer->family?->initialCentre?->sponsor?->name,
                    ]);

                    // stream a few to the user, keep-alive
                    if (++$counter % $chunkSize === 0) {
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                });

            fclose($output);
        }, "carers-export_$now.csv", [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
