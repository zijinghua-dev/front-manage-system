<?php

namespace Zijinghua\Zvoyager\Http\Middlewares;

use Closure;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param mixed ...$guards
     * @return mixed|Response
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $token = $this->authenticate($request, $guards);

        $response = $next($request);

        if ($token) {
            $returnData = $response->getData(true);
            $returnData['token'] = $token;
            $result = new Response(json_encode($returnData));
        } else {
            $result = $response;
        }

        return $result;
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        $driver = null;
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard);
                $token = $this->getToken($guard);
                return $token;
            }
        }

        $this->unauthenticated($request, $guards);
    }

    /**
     * 获取token
     * @param $request
     * @param $driver
     * @return |null
     * @throws \Exception
     */
    protected function getToken($driver)
    {
        $token = null;
        $zgaurd = auth($driver);
        $payload = $zgaurd->getPayload()->get();
        $freshPeriod = getConfigValue('zbasement.token.refresh_period');
        if (($payload['exp'] - time()) < $freshPeriod) {
            $token = $zgaurd->refresh();
        }
        return $token;
    }
}
