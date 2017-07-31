<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Get the guard to be used during password reset.
     *
     * @return Guard
     */
    protected function guard()
    {
        return Auth::guard('api');
    }



    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        // required to point the api at the
        return Password::broker('users');
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->forceFill([
            'password' => bcrypt($password),
            'remember_token' => Str::random(60),
        ])->save();

        // we aren't logging them in, we're redirecting them to an app page
        // $this->guard()->login($user);
    }


    /*
    * Get the response for after a successful password reset.
    *
    * @param string $response
    * @return \Symfony\Component\HttpFoundation\Response
    */
    protected function sendResetResponse($response)
    {
        return response()->json(['status' => trans($response)]);
    }

    /**
     * Get the response for after a failing password reset.
     *
     * @param Request $request
     * @param string $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return response()->json(['email' => trans($response)], 422);
    }
}
