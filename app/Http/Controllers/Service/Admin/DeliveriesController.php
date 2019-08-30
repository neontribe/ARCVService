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
            "range" => "KIL0075 - KIL0056",
            "centre" => "Kilmarnochshire CC",
            "date" => "20-08-2019"
            ],
            (object)[
            "range" => "KIL0075 - KIL0056",
            "centre" => "Kilmarnochshire CC",
            "date" => "20-08-2019"
            ]
        ]);
        
        $sponsors = Sponsor::get();
        return view('service.deliveries.index', compact('deliveries'));
    }
}