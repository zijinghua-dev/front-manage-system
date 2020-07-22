<?php

namespace Zijinghua\Zvoyager\App\Guards;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;
use Zijinghua\Zvoyager\App\Providers\ClientRestfulUserProvider;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ZGuard extends JWTGuard
{
    /**
     * The user we last attempted to retrieve.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $lastAttempted;
    /**
     * Instantiate the class.
     *
     * @param  \Tymon\JWTAuth\JWT  $jwt
     * @param  \App\Providers\ClientRestfulUserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     *
     * @return void
     */
    public function __construct(JWT $jwt, ClientRestfulUserProvider $provider, Request $request)
    {
        $this->jwt = $jwt;
        $this->provider = $provider;
        $this->request = $request;
    }
    /**
     * Attempt to authenticate the user using the given credentials and return the token.
     *
     * @param  array  $credentials
     * @param  bool  $login
     *
     * @return bool|string
     */
    public function attempt(array $credentials = [], $login = true)
    {
        $result = $this->provider->retrieveByCredentials($credentials);

        if (!$result instanceof User && is_array($result)) {
            $result['message'] = config('zvoyager.auth.failed.user_has_not_exists.message');
            return $result;
        }
        $this->lastAttempted = $user = $result;

        $validateResult = $this->hasValidCredentials($user, $credentials);
        if (!is_array($validateResult)) {
            return $login ? $this->login($user) : true;
        } else {
            return $validateResult;
        }
    }

    /**
     * Return Validate Result
     *
     * @param  mixed  $user
     * @param  array  $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        if (Hash::check($credentials['password'], $user->password)) {
            return $user;
        }
        $result = [
            'message' => config('zvoyager.auth.failed.validation.message'),
            'data' => [],
            'status' => false,
            'code' => null
        ];
        return $result;
    }
}
