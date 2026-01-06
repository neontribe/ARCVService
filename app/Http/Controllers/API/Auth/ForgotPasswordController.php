<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Password;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Log;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    /**
     * Get the broker to be used during password reset.
     *
     * @return PasswordBroker
     */
    protected function broker(): PasswordBroker
    {
        return Password::broker('users');
    }

    /**
     * Send a reset link to the given user.
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $this->validate($request, ['email' => 'required|email']);
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        // sends `passwords.sent` from the lang files
        if ($response === Password::RESET_LINK_SENT) {
            Log::info('Rosie account reset password link email sent.');
            return response()->json(['status' => trans($response)]);
        }

        // account enumeration protection - an invalid email response returns a 200.
        // the $reponse key `passwords.users` is the same as `passwords.sent` above
        if ($response === Password::INVALID_USER) {
            return response()->json(['status' => trans($response)]);
        }

        // any other error is a 422
        return response()->json(['email' => trans($response)], 422);
    }
}
