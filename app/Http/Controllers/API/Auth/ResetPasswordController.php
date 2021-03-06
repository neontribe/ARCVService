<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
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
     * @return Guard|StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('api');
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    public function broker()
    {
        // required to point the api at the correct correct user/password table
        return Password::broker('users');
    }

    /**
     * Reset the given user's password.
     *
     * @param CanResetPassword $user
     * @param string $password
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

    /**
     * Get the response for after a successful password reset.
     *
     * @param Request $request
     * @param $response
     * @return JsonResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        return response()->json(['status' => trans($response)]);
    }

    /**
     * Get the response for after a failing password reset.
     *
     * @param Request $request
     * @param string $response
     * @return JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return response()->json(['email' => trans($response)], 422);
    }
}
