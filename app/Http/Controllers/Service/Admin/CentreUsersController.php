<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\CentreUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewCentreUserRequest;
use App\Sponsor;
use DB;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Log;
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
     * Show the form for creating new CentreUsers
     *
     * @return Factory|View
     */
    public function create()
    {
        $centres = Centre::get(['name','id']);
        return view('service.centreusers.create', compact('centres'));
    }

    /**
     * Show the form for editing a CentreUser
     *
     * @param $id
     * @return Factory|View
     */
    public function edit($id)
    {
        // Find the worker or throw a 500
        $worker = CentreUser::findOrFail($id);

        // Get the homeCentreId
        $homeCentreId = $worker->homeCentre->id;

        // Work out current worker-centre selections
        $workerCentres = [
            "home" => $homeCentreId,
            "alternates" => $worker
                ->centres()
                ->whereKeyNot($homeCentreId)
                ->pluck('id')
                ->all()
        ];

        // Saucy eager loading to get sponsors and their centres
        $centresBySponsor = Sponsor::with(['centres:sponsor_id,id,name'])
            ->get(['id', 'name'])
            // Set some flags on those.
            ->each(function ($sponsor) use ($workerCentres) {
                $sponsor->centres->each(function ($centre) use ($workerCentres) {
                    if ($centre->id ===  $workerCentres["home"]) {
                        $centre->selected = "home";
                    } elseif (in_array($centre->id, $workerCentres["alternates"])) {
                        $centre->selected = "alternate";
                    } else {
                        $centre->selected = false;
                    }
                });
            });

        return view('service.centreusers.edit', compact('worker', 'centresBySponsor'));
    }
    
    public function update(AdminNewCentreUserRequest $request, $id)
    {
        try {
            $centreUser = DB::transaction(function () use ($request, $id) {

                // Update a CentreUser;
                $cu = CentreUser::findOrFail($id);

                // Update the system
                $cu->fill([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                ]);
                $cu->save();

                // Sync those;
                $this->syncCentres($request, $cu);

                return $cu;
            });
        } catch (Exception $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.centreusers.edit', ['id' => $id ])
                ->withErrors('Update failed - DB Error.');
        }
        return redirect()
            ->route('admin.centreusers.index')
            ->with('message', 'Worker ' . $centreUser->name . ' updated');
    }

    public function store(AdminNewCentreUserRequest $request)
    {
        try {
            $centreUser = DB::transaction(function () use ($request) {
                // Create a CentreUser;
                $cu = new CentreUser([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    // Set random password
                    'password' => bcrypt(Uuid::uuid4()->toString())
                ]);
                $cu->save();

                // Sync those;
                $this->syncCentres($request, $cu);

                // Pop the centre out.
                return $cu;
            });
        } catch (Exception $e) {
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

    /**
     * Code deduplication;
     * @param $request
     * @param CentreUser $cu
     * @return array
     */
    private function syncCentres($request, CentreUser $cu)
    {
        // Set Home Centre
        $homeCentre_id = $request->input('worker_centre');

        // Create the minimum sized array for syncing
        $centre_ids = [];
        $centre_ids[$homeCentre_id] = ['homeCentre' => true];

        // Batch up all the centre ids
        if ($request->has('alternative_centres')) {
            $alt_ids = $request->input('alternative_centres.*');
            foreach ($alt_ids as $id) {
                // set the key/value for sync
                $centre_ids[$id] = ['homeCentre' => false];
            }
        }
        // Sync them setting pivots.
        return $cu->centres()->sync($centre_ids);
    }
}
