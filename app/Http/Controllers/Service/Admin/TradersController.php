<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewTraderRequest;
use App\Http\Requests\AdminUpdateTraderRequest;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class TradersController extends Controller
{
    /**
     * List Traders
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $traders = Trader::with(['users', 'market'])->get();
        return view('service.traders.index', compact('traders'));
    }

    /**
     * Store a new Trader
     *
     * @param AdminNewTraderRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(AdminNewTraderRequest $request)
    {
        try {
            $trader = DB::transaction(function () use ($request) {
                // find our market or die
                $m = Market::findOrFail($request->input('market'));

                // make a trader
                $t = Trader::create([
                    'name' => $request->name,
                    'location' => $request->location,
                    'market_id' => $m->id,
                ]);

                // get our updated or new users as a model collection
                $users = $this->createOrUpdateUsersFromInput(
                    // pull the users as an array from the input
                    (array)$request->get('users')
                );

                // users should be a collection; sync them to our trader
                $t->users()->sync($users);
                return $t;
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('admin.traders.create')->withErrors('Creation failed - DB Error.');
        }
        return redirect()
            ->route('admin.traders.index')
            ->with('message', 'Trader ' . $trader->name . ' created');
    }

    /**
     * @param array $users
     * @return Collection
     */
    private function createOrUpdateUsersFromInput(array $users = [])
    {
        return collect(array_map(function ($data) {
            // check if it exists by email.
            $user = User::firstWhere('email', $data['email']);

            // if so...
            if ($user) {
                // and the name has changed...
                if ($user->name !== $user["name"]) {
                    // update it...
                    $user->name = $user["name"];
                    $user->save();
                }
                // then return it.
                return $user;
            } else {
                // or just make a new one.
                return User::create([
                    'name' => $user['name'],
                    'email' => $user['email'],
                    // didn't supply a password? make one.
                    'password' => bcrypt($user['password'] ?? md5(rand())),
                ]);
            }
        }, $users));
    }

    /**
     * Show the create form
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $marketsBySponsor = Sponsor::with(['markets:sponsor_id,id,name'])->get(['id', 'name']);
        return view('service.traders.create', compact('marketsBySponsor'));
    }

    /**
     * Show the edit form
     *
     * @param int $id
     * @return Application|Factory|View
     */
    public function edit(int $id)
    {
        $trader = Trader::with(['users', 'market'])->find($id);
        $marketsBySponsor = Sponsor::with(['markets:sponsor_id,id,name'])->get(['id', 'name']);
        return view('service.traders.edit', compact('marketsBySponsor', 'trader'));
    }

    /**
     * Update the trader AND set the users
     *
     * @param AdminUpdateTraderRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(AdminUpdateTraderRequest $request, int $id)
    {
        try {
            $trader = DB::transaction(function () use ($request, $id) {
                // find the trader
                $t = Trader::findOrFail($id);

                // Update it
                $t->fill([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'location' => $request->input('location')
                ])->save();

                // get our updated or new users as a model collection
                $users = $this->createOrUpdateUsersFromInput(
                    // pull the users as an array from the input
                    (array)$request->get('users')
                );

                // get our trader's original user IDs.
                $origUserIds = $t->users()
                    ->pluck('id')
                    ->toArray();

                // sync the current users to our trader.
                $t->users()->sync($users);

                // remove any users that have just been deleted and have *no other* traders
                User::whereIn('id', $origUserIds)
                    ->withCount("traders")
                    ->having('traders_count', '=', 0)
                    ->delete();

                return $t;
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()
                ->route('admin.traders.edit', ['id' => $id])
                ->withErrors('Update failed - DB Error.');
        }
        return redirect()
            ->route('admin.traders.index')
            ->with('message', 'Trader ' . $trader->name . ' updated');
    }
}
