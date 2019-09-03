<?php
namespace App\Http\Controllers\Service\Admin;
use App\Http\Controllers\Controller;
use App\Sponsor;

class DeliveriesController extends Controller
{
    /**
     * Display a listing of Sponsors.
     *
     * @return json
     */
    public function index()
    {
        $deliveries = collect([
           (object)[
                "range" => "KIL0075 - KIL0056",
                "centre" => "Kilmarnochshire CC",
                "date" => "20-08-2019"
           ],
           (object)[
                "range" => "BRO0032 - BRO0033",
                "centre" => "Bromptonshire CC",
                "date" => "22-08-2019"
            ],
            (object)[
                "range" => "SVE0075 - SVE0056",
                "centre" => "Svelteshire CC",
                "date" => "21-08-2019"
            ],
            (object)[
                "range" => "DRO0023 - DRO0054",
                "centre" => "Droptonshire CC",
                "date" => "29-08-2019"
            ],
            (object)[
                "range" => "DRO0013 - DRO0024",
                "centre" => "Droptonshire CC",
                "date" => "20-08-2019"
            ],
        ]);
        
        $sponsors = Sponsor::get();
        return view('service.deliveries.index', compact('deliveries'));
    }
}