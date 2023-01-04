<?php

namespace App\Exceptions;

use Auth;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
        ValidationException::class,
    ];

    // These produce a single line log
    protected $dontStackTrace = [
        HttpResponseException::class,
        MethodNotAllowedHttpException::class,
        OAuthServerException::class,
    ];

    /**
     * Check an exception is worth a stacktrace
     *
     * @param Exception $e Exception
     * @return bool;
     */
    protected function shallWeStackTrace(Throwable $e)
    {
        if (config('app.env') === 'production') {
            foreach ($this->dontStackTrace as $type) {
                if ($e instanceof $type) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @throws Exception
     * @param  Exception  $e
     * @return mixed
     */
    public function report(Throwable $e)
    {
        if (!$this->shallWeStackTrace($e)) {
            try {
                // Should build an appropriate logger for our config;
                // Alas, Log::shouldReceive can't detect this.
                $logger = $this->container->make(LoggerInterface::class);
            } catch (Exception $ex) {
                // Whoops, the logger didn't get made, throw the original exception
                throw $e;
            }
            // Poor man's one-line error; If you see lots of these, investigate.
            $logger->error(
                get_class($e). ": ".
                $e->getTrace()[0]['function']
                .' on line '
                . $e->getTrace()[0]['line']
                .' of file '
                .$e->getTrace()[0]['file']
            );
        } else {
            return parent::report($e);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  Request  $request
     * @param  Exception  $exception
     * @return RedirectResponse|Redirector|Response
     */
    public function render($request, Throwable $exception)
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
            // Send them somewhere safe. '' is the "/" equivalent.
            return redirect('');
                // For some reason ->withErrors() won't "take";
                // The session gets flashed, but it doesn't survive.
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  Request  $request
     * @param AuthenticationException $exception
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
