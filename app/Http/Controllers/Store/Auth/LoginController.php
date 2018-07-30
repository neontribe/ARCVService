<?php

namespace App\Http\Controllers\Service\Auth;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    /**
     * Show the login form.
     *
     * @return view
     */
    public function showLoginForm()
    {
        return view('service.auth.login');
    }

    public function login(Request $request)
    {
        // Validate the form data
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        // Attempt to log the user in
        if (Auth::guard('web')->attempt(
            ['email' => $request->email, 'password' => $request->password],
            $request->remember
        )) {
            // if successful, then redirect to their intended location
            return redirect()->intended(route('service.dashboard'));
        }

        // Throttle uses AuthenticatesUser trait's ThrottleLogins.
        // Default 5 attempts per minute per email+IP.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        // Login has Failed - increment attempts.
        $this->IncrementLoginAttempts($request);
        Log::info(
            'Attempt to login with wrong credentials by '
            . $request->email . ' on '
            . $request->ip()
        );

        // If unsuccessful, then redirect back to the login with the form data
        // along with a message indicating the problem.
        return redirect()
            ->route('service.login')
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['error_message' => trans('auth.failed')])
            ;
    }

    public function logout(Request $request)
    {
        // Logout current user
        Auth::logout();
        // Boot to login page
        return redirect()->route('service.login')->with("messages");
    }
}
