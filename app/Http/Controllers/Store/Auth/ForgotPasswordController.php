<?php

namespace App\Http\Controllers\Service\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Override to use service auth view.
     */
    public function showLinkRequestForm()
    {
        return view('service.auth.passwords.email');
    }

    /**
     * Override to make sure we don't back() by referrer
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $response = $this->broker()->sendResetLink(
            $request->only('email')
        );

        $back_url = $request->session()->get('_previous')['url'];

        switch ($response) {
            case Password::RESET_LINK_SENT:
                return redirect()->to($back_url)->with('status', trans($response));
            default:
                return redirect()->to($back_url)->withErrors(
                    ['email' => trans($response)]
                );
        }
    }
}
