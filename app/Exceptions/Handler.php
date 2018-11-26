<?php

namespace App\Exceptions;

use Auth;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            // Explicitly redirect to login form if token mismatches.
            // Note: this will also draw attention to the login form timing out.
            Auth::logout();
            return redirect()
                ->guest('/login')
                // We anticipate expiry to be the most common reason.
                ->withErrors(['error_message' => trans('auth.expired')])
                ;
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            // User somehow tried an HTTP verb a resource doesn't support
            // Send them somewhere safe.
            return redirect()
                // For some reason ->withErrors() won't "take";
                // The session gets flashed, but it doesn't survive.
                ->route('/');
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        // For later if we have different users.

        $guard = array_get($exception->guards(), 0);
        switch ($guard) {
            case 'api':
                $login = route('api.login');
                break;
            //TODO: merge
            case 'store':
                $login = route('store.login');
                break;
            case 'admin':
            default:
                $login = route('admin.login');
                break;
        }

        return redirect()->guest($login)
            ->withErrors(['error_message' => trans('auth.exception')]);
    }
}
