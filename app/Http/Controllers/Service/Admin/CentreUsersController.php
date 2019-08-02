<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\CentreUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewCentreUserRequest;
use DB;
use Log;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Ramsey\Uuid\Uuid;

class CentreUsersController extends Controller
{
    /**
     * Display a listing of Workers.
     *
     * @return Factory|View
     */
    public function index()
    {
        $workers = CentreUser::get();
        return view('service.centreusers.index', compact('workers'));
    }

     /**
     * Show the form for creating new Workers.
     *
     * @return Factory|View
     */
    public function create()
    {
        $centres = Centre::get(['name','id']);
        return view('service.centreusers.create', compact('centres'));
    }

    public function edit($id)
    {
        $worker = CentreUser::findOrFail($id);

        $data = [
            "worker" => $worker,
            "homeCentreNeighbours" => $worker->homeCentre[0]->neighbours(),
        ];

        return view('service.centreusers.edit', $data);
    }
    
    public function update($id)
    {
        //TBFI
    }

    public function store(AdminNewCentreUserRequest $request)
    {
        try {
            $centreUser = DB::transaction(function () use ($request) {
                // Create a CentreUser;
                $c = new CentreUser([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    // Set random password
                    'password' => bcrypt(Uuid::uuid4()->toString())
                ]);
                $c->save();

                // Set Home Centre
                $centre_id = $request->input('worker_centre');
                $c->centres()->attach($centre_id, ['homeCentre' => true]);

                if ($request->has('alternative_centres.*')) {
                    $alt_ids = $request->input('alternative_centres.*');
                    // Pivot table defaults to `homeCentre` = false, don't need to set it;
                    $c->centres()->attach($alt_ids);
                }
                return $c;
            });
        } catch (\Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('admin.centreusers.create')->withErrors('Creation failed - DB Error.');
        }
        return redirect()
            ->route('admin.centreusers.index')
            ->with('message', 'Worker ' . $centreUser->name . ' created');
    }
}
