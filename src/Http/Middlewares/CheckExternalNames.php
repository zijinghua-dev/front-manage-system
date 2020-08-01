<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class CheckExternalNames
{
    public function handle($request, Closure $next)
    {
        $service=Zsystem::service('parameter');
        $data=$service->decryExternal($request);
        $request->replace($data);
        return $next($request);
    }
}