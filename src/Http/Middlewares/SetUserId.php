<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Illuminate\Support\Facades\Auth;

class SetUserId
{
    public function handle($request, Closure $next)
    {
        //把userId写入request
        $data=$request->all();
        $data['userId']=Auth::user()->id;
        $request->replace($data);
        return $next($request);
    }
}