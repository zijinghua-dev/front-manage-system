<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;

class InGroup
{
    public function handle($request, Closure $next)
    {
        //show,update，delete这些操作都有操作对象，
        //要识别用户和这个对象是否在一个组内
        //用户的组：最高的，且互相不重复的
        //操作对象的组
    }
}