<?php

namespace App\Http\Controllers\API\Auth;

use App\User;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Log;
use Symfony\Component\HttpFoundation\Request;

class LoginProxy
{
    const REFRESH_TOKEN = 'refresh_token';

    private $auth;
    private $cookie;
    private $db;
    private $request;
    private $user;

    /**
     * LoginProxy constructor.
     *
     * @param Application $app
     * @param User $user
     */
    public function __construct(Application $app, User $user)
    {
        $this->user = $user;
        $this->auth = $app->make('auth');
        $this->cookie = $app->make('cookie');
        $this->db = $app->make('db');
        $this->request = $app->make('request');
    }

    /**
     * Attempt to create an access token using user credentials
     * @param $email
     * @param $password
     * @return \Illuminate\Contracts\Foundation\Application|ResponseFactory|JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     */
    public function attemptLogin($email, $password)
    {
        /*
         * So SQLLite and MySQL treat "having" differently
         * - SQLLite requires a "group by" for a "having"
         * - MySQL is happy to assume a single group if unspecified.
         */
        // fetch the users with the email
        $user = User::where('email', $email)
            // that have at least one enabled
            ->withCount([
                'traders' => function ($query) {
                    return $query->whereNull('traders.disabled_at');
                },
            ])
            // group on email to keep SQLlite happy
            ->groupBy('email')
            ->having('traders_count', '>', 0)
            ->first();

        if (!is_null($user)) {
            return $this->proxy('password', [
                'username' => $email,
                'password' => $password
            ]);
        }

        // Log the failed attempt.
        Log::info('Login attempt with invalid credentials for ' . $email . '.');
        // Mimic the OAuthServerException invalidCredentials
        return response([
            'error' => 'invalid_credentials',
            'message' => trans('api.errors.invalid_credentials'),
        ], 401);
    }

    /**
     * Attempt to refresh the access token used a refresh token that
     * has been saved in a cookie
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Exception
     */
    public function attemptRefresh()
    {
        $refresh_token = $this->request->refresh_token; //cookie(self::REFRESH_TOKEN);

        return $this->proxy('refresh_token', [
            'refresh_token' => $refresh_token
        ]);
    }

    /**
     * Proxy a request to the OAuth server.
     *
     * @param $grantType
     * @param array $data
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Exception
     */
    public function proxy($grantType, array $data = [])
    {
        $data = array_merge($data, [
            'client_id'     => (int) config('passport.password_client'),
            'client_secret' => config('passport.password_client_secret'),
            'grant_type'    => $grantType,
            'scope' => '*',
        ]);

        // make the request
        $request = Request::create('/oauth/token', 'POST', $data);

        // get the app;
        $app = app();
        // get the router
        $router = $app['router'];
        // get the response
        $response = $router->prepareResponse($request, $app->handle($request));

        if (!$response->isSuccessful()) {
            return $response;
        }

        $data = json_decode($response->getContent());
        // Create a refresh token cookie
        $this->cookie->queue(
            self::REFRESH_TOKEN,
            $data->refresh_token,
            604800, // 7 days
            null,
            null,
            false,
            true // HttpOnly
        );

        return response()->json([
            'access_token' => $data->access_token,
            'expires_in' => $data->expires_in,
            'refresh_token' => $data->refresh_token,
        ]);
    }

    /**
     * Logs out the user. We revoke access token and refresh token.
     * Also instruct the client to forget the refresh cookie.
     */
    public function logout()
    {
        $accessToken = $this->auth->user()->token();

        // Revoke the refreshToken.
        $this->db
            ->table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ])
        ;

        $accessToken->revoke();

        $this->cookie->queue($this->cookie->forget(self::REFRESH_TOKEN));
    }
}
