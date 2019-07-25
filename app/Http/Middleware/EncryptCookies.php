<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * Indicates if cookies should be serialized.
     * This was default off in 5.5.42 - if a bad actor has the app_key they can spoof cookies
     * We are re-enabling it.
     *
     * @var bool
     */
    protected static $serialize = true;
}
