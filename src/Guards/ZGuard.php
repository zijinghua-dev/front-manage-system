<?php

namespace Zijinghua\Zvoyager\Guards;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;
use Zijinghua\Zvoyager\Providers\ClientRestfulUserProvider;
use App\Models\User;

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

    public function attemptExternal(array $credentials = [], $login = true)
    {
        $result = $this->provider->retrieveByCredentials($credentials);
        if (!isset($result)) {
            //如果没有，则注册一个
            $result = $this->provider->createByCredentials($credentials);
        }
        $this->lastAttempted = $user = $result;

            if ($login) {
                $this->login($user);
            }
            return $this->lastAttempted;
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
        if (!isset($result)) {
            return $result;
        }
        $this->lastAttempted = $user = $result;

        if ($this->hasValidCredentials($user, $credentials)) {
            if($login){
                $this->login($user);
            }
            return $this->lastAttempted;
        }

        return false;
//
//        $validateResult = $this->hasValidCredentials($user, $credentials);
//        if (isset($validateResult['status']) && $validateResult['status']) {
//            return $login ? $this->login($user) : true;
//        } else {
//            return $validateResult;
//        }
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
        $result = $this->provider->validateCredentials($user, $credentials);
        return $result;
    }
}
