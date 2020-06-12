<?php

namespace App\Http\Controllers\API\Auth;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Log;
use App\User;

class LoginProxy
{
    const REFRESH_TOKEN = 'refresh_token';

    private $auth;
    private $cookie;
    private $db;
    private $request;
    private $user;

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
     *
     * @param string $email
     * @param string $password
     */
    public function attemptLogin($email, $password)
    {
        $user = User::where('email', $email)->first();

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
     * @param string $grantType what type of grant type should be proxied
     * @param array $data the data to send to the server
     */
    public function proxy($grantType, array $data = [])
    {
        $data = array_merge($data, [
            'client_id'     => (int) config('passport.password_client'),
            'client_secret' => config('passport.password_client_secret'),
            'grant_type'    => $grantType,
            // All users equal for now. No scopes.
            'scope' => '',
        ]);

        $request = Request::create('/oauth/token', 'POST', $data);
        $response = Route::dispatch($request);

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
