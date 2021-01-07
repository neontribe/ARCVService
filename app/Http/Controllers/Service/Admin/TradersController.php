<?php

namespace App\Http\Controllers\Service\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminNewUpdateTraderRequest;
use App\Market;
use App\Sponsor;
use App\Trader;
use App\User;
use Carbon\Carbon;
use Debugbar;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laracsv\Export;
use Ramsey\Uuid\Uuid;
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
        $traders = $this->getTraders();
        return view('service.traders.index', compact('traders'));
    }

    /**
     * Store a new Trader
     *
     * @param AdminNewUpdateTraderRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(AdminNewUpdateTraderRequest $request)
    {
        try {
            $trader = DB::transaction(function () use ($request) {
                // find our market or die
                $m = Market::findOrFail($request->input('market'));

                // make a trader
                $t = Trader::create([
                    'name' => $request->name,
                    'market_id' => $m->id,
                ]);

                // get our updated or new users as ids
                $userIds = $this->createOrUpdateUsersFromInput(
                    (array)$request->get('users')
                );

                // users should be an array; sync them to our trader
                $t->users()->sync($userIds);
                return $t;
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            // Throw it back to the user
            return redirect()->route('admin.traders.create')->withErrors('Creation failed - DB Error.');
        }
        return redirect()
            ->route('admin.traders.index')
            ->with('message', 'Trader ' . $trader->name . ' created');
    }

    /**
     * @param array $userData
     * @return array|int[]
     */
    private function createOrUpdateUsersFromInput(array $userData = [])
    {
        return array_map(function ($data) {
            // check if it exists by email.
            $user = User::firstWhere('email', $data['email']);

            // if so...
            if ($user) {
                // and the name has changed...
                if ($user->name !== $data["name"]) {
                    // update it...
                    $user->name = $data["name"];
                    $user->save();
                }
                // then return it.
                return $user->id;
            } else {
                // or just make a new one.
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt($data['password'] ?? md5(rand())),
                ]);
                return $user->id;
            }
        }, $userData);
    }

    /**
     * Show the create form
     *
     * @return Application|Factory|View
     */
    public function create(Request $request)
    {
        // Do a quick validate
        $validData = $request->validate([
            'market' => 'integer|exists:markets,id',
        ]);

        $preselected = $validData['market'] ?? null;

        $marketsBySponsor = Sponsor::with(['markets:sponsor_id,id,name'])->get(['id', 'name']);
        return view('service.traders.create', compact('marketsBySponsor', 'preselected'));
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
     * @param AdminNewUpdateTraderRequest $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(AdminNewUpdateTraderRequest $request, int $id)
    {
        try {
            $trader = DB::transaction(function () use ($request, $id) {
                // find the trader
                $t = Trader::findOrFail($id);

                // make an array of things to fill
                $fillArray = [
                    'market_id' => $request->input('market'),
                    'name' => $request->input('name'),
                ];

                // If form's disabled input and Trader's disabled_at are in different states
                if (boolval($request->input('disabled')) xor
                    boolval($t->disabled_at)
                ) {
                    // ... then something's changed... set by input
                    $fillArray['disabled_at'] = (boolVal($request->input('disabled')))
                        // true-ish, make a date
                        ? Carbon::now()
                        // false-ish, set it null
                        : null;
                }
                // ... otherwise don't alter it.

                // Update trader and save
                $t->fill($fillArray)->save();

                // get our trader's original user IDs.
                $origUserIds = $t->users()
                    ->pluck('id')
                    ->toArray();

                // get our updated or new users as ids
                $userIds = $this->createOrUpdateUsersFromInput(
                    // pull the users as an array from the input
                    (array)$request->get('users')
                );

                // users should be an array; sync them to our trader
                $t->users()->sync($userIds);

                // did we disable a trader up there?
                if (array_key_exists('disabled_at', $fillArray) &&
                    !is_null($fillArray['disabled_at'])
                ) {
                    // force log-out any users who are or were in a newly *disabled* trader
                    $affectedUserIds = array_unique(array_merge($origUserIds, $userIds));

                    User::with(['tokens'])
                        ->whereIn('id', $affectedUserIds)
                        ->each(function ($user) {
                            // nicked from the loginProxy
                            $accessToken = $user->token();
                            if ($accessToken) {
                                Log::info('removing refresh tokens for user' . $user->id);
                                // Revoke the refreshToken.
                                DB::table('oauth_refresh_tokens')
                                    ->where('access_token_id', $accessToken->id)
                                    ->update([
                                        'revoked' => true,
                                    ]);

                                Log::info('removing token for user '. $user->id);
                                $accessToken->revoke();
                            } else {
                                Log::info('no tokens to revoke');
                            }
                        });
                }

                // find any users that have just been deleted and have *no other* traders
                $orphanUsers = User::whereIn('id', $origUserIds)
                    ->withCount('traders')
                    // to keep SQLite happy
                    ->groupBy('id')
                    ->having('traders_count', '=', 0)
                    ->get();

                // this is a slow iteration, but we don't expect too many users/trader
                $orphanUsers->each(function ($orphanUser) {
                    // make a Uuid for the email, which has a unique constraint
                    $hash = Uuid::uuid4()->toString();
                    // localhost - if it gets emailed, somehow, it'll be looped back to the box.
                    $orphanUser->update(['email' => $hash . '@localhost']);
                    // ... and soft-delete them, since they are orphaned
                    $orphanUser->delete();
                });

                return $t;
            });
        } catch (Throwable $e) {
            // Oops! Log that
            Log::error('Bad transaction for ' . __CLASS__ . '@' . __METHOD__ . ' by service user ' . Auth::id());
            Log::error($e->getMessage());
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

    public function download()
    {
        $traders = $this->getTraders();
        $csvExporter = new Export();
        $csvExporter->beforeEach(function ($trader) {
            foreach ($trader->users->sortBy('name') as $user) {
                $userNames[] = $user->name;
                $trader->users = implode(', ', $userNames);
            }
        });

        $header = [
            'name' => 'Name',
            'market.name' => 'Market',
            'market.sponsor.name' => 'Area',
            'users' => 'Users',
            'created_at' => 'Join Date',
            'deleted_at' => 'Leaving Date',
        ];

        $fileName = 'active_traders.csv';
        $buildFile = $csvExporter->build($traders, $header);

        /**
         * * have to disable the debugbar on local on the fly as it conflicts with LaraCSV
         * * that is not yet fixed https://github.com/usmanhalalit/laracsv/issues/34
         */
        if (Debugbar::isEnabled()) {
            app('debugbar')->disable();
        }

        $buildFile->download($fileName);
    }

    private function getTraders()
    {
        $traders = Trader::with(['users', 'market'])->get();
        // TODO : efficiency
        $traders = $traders->sortBy(function ($trader) {
            return $trader->market->sponsor->name . '#' .
                $trader->market->name . '#' .
                $trader->name;
        });

        return $traders;
    }
}
