<?php

namespace App\Http\Controllers\Service\Admin;

use App\Centre;
use App\CentreUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminIndexCentreUsersRequest;
use App\Http\Requests\AdminNewCentreUserRequest;
use App\Http\Requests\AdminUpdateCentreUserRequest;
use App\Sponsor;
use DB;
use Debugbar;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laracsv\Export;
use League\Csv\CannotInsertRecord;
use Log;
use Ramsey\Uuid\Uuid;
use Throwable;

class CentreUsersController extends Controller
{
    /**
     * Display a listing of Workers.
     * @param AdminIndexCentreUsersRequest $request
     * @return Application|Factory|View
     */
    public function index(AdminIndexCentreUsersRequest $request): View|Factory|Application
    {
        // fetch query params from request
        $field = $request->input('orderBy');
        $direction = $request->input('direction');

        $sorter = match ($field) {
            'name' => 'name',
            'homeCentreArea' => 'homeCentre.sponsor.name',
            'homeCentre' => 'homeCentre.name',
            default => function ($item) {
                $homeCentre = $item->homeCentre;
                return $homeCentre->sponsor->name . '#' . $homeCentre->name . '#' . $item->name;
            },
        };
        $workers = CentreUser::get()->sortBy($sorter, SORT_REGULAR, ($direction === 'desc'));

        return view('service.centreusers.index', compact('workers'));
    }

    /**
     * Show the form for creating new CentreUsers
     * @return Factory|View|Application
     */
    public function create(): Factory|View|Application
    {
        $centres = Centre::get(['name', 'id']);
        return view('service.centreusers.create', compact('centres'));
    }

    /**
     * Show the form for editing a CentreUser.
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id): View|Factory|Application
    {
        // Find the worker or throw a 500
        $worker = CentreUser::findOrFail($id);

        // Get the homeCentreId
        $homeCentreId = $worker->homeCentre->id;

        // Work out current worker-centre selections
        $workerCentres = [
            "home" => $homeCentreId,
            "alternates" => $worker->centres()->whereKeyNot($homeCentreId)->pluck('id')->all(),
        ];

        // Saucy eager loading to get sponsors and their centres
        $centresBySponsor = Sponsor::with(['centres:sponsor_id,id,name'])->get(['id', 'name'])
            // Set some flags on those.
            ->each(function ($sponsor) use ($workerCentres) {
                $sponsor->centres->each(function ($centre) use ($workerCentres) {
                    if ($centre->id === $workerCentres["home"]) {
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

    /**
     * Update a CentreUser from a form
     * @param AdminUpdateCentreUserRequest $request
     * @param $id
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(AdminUpdateCentreUserRequest $request, $id)
    {
        try {
            $centreUser = DB::transaction(function () use ($request, $id) {
                // Update a CentreUser;
                $cu = CentreUser::findOrFail($id);

                // Update the system
                $cu->fill([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'downloader' => $request->input('downloader'),
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
            return redirect()->route('admin.centreusers.edit', ['id' => $id])
                ->withErrors('Update failed - DB Error.');
        }
        return redirect()->route('admin.centreusers.index')
            ->with('message', 'Worker ' . $centreUser->name . ' updated');
    }

    /**
     * Code deduplication;
     * @param Request $request
     * @param CentreUser $cu
     * @return array
     */
    private function syncCentres(Request $request, CentreUser $cu): array
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

    /**
     * Create a CentreUser from a form
     * @param AdminNewCentreUserRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(AdminNewCentreUserRequest $request): RedirectResponse
    {
        try {
            $centreUser = DB::transaction(function () use ($request) {
                // Create a CentreUser;
                $cu = new CentreUser([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    // Set random password
                    'password' => bcrypt(Uuid::uuid4()->toString()),
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
            return redirect()->route('admin.centreusers.create')->withErrors('Creation failed - DB Error.');
        }
        return redirect()->route('admin.centreusers.index')->with('message',
            'Worker ' . $centreUser->name . ' created');
    }

    /**
     * @return void
     * @throws CannotInsertRecord
     */
    public function download(): void
    {
        $workers = CentreUser::get()->sortBy(function ($item) {
            $homeCentre = $item->homeCentre;
            return $homeCentre->sponsor->name . '#' . $homeCentre->name . '#' . $item->name;
        });

        $csvExporter = new Export();

        /**
         * * modify downloader values to print user friendly text
         * * currently, the y/n in the model reverts to 0/1 because of Laravel casting
         * * so we are creating a temporary property
         */
        $csvExporter->beforeEach(function ($worker) {
            $worker->downloaderRole = $worker->downloader ? 'Yes' : 'No';
            foreach ($worker->centres as $centre) {
                if ($centre->id !== $worker->homeCentre->id) {
                    $centreNames[] = $centre->name;
                    $worker->alternative_centres = implode(', ', $centreNames);
                }
            }
        });

        $header = [
            'name' => 'Name',
            'email' => 'E-mail Address',
            'homeCentre.sponsor.name' => 'Home Centre Area',
            'homeCentre.name' => 'Home Centre',
            'alternative_centres' => 'Alternative Centre',
            'downloaderRole' => 'Downloader',
        ];

        $fileName = 'active_workers.csv';
        $buildFile = $csvExporter->build($workers, $header);

        /**
         * * have to disable the debugbar on local on the fly as it conflicts with LaraCSV
         * * that is not yet fixed https://github.com/usmanhalalit/laracsv/issues/34
         */
        if (Debugbar::isEnabled()) {
            app('debugbar')->disable();
        }

        $buildFile->download($fileName);
    }

    /**
     * Handle deleting a centre user
     * @param int $id
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        $centreUser = CentreUser::findOrFail($id);
        $centreUser->centre->centreUsers()->detach($id);
        $centreUser->delete();
        return redirect()->route('admin.centreusers.index')
            ->with('message', 'Worker ' . $centreUser->name . ' deleted');
    }
}
