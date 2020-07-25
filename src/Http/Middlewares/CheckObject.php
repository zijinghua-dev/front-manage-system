<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class CheckObject
{
    public function handle($request, Closure $next)
    {
        //检查这个对象在这个组有没有可操作的权限
        $slug=getSlug($request);
        $objectId=$request['objectId'];
        $groupId=getGroup($request);
        //首先是这个组能否操作这个类型的对象
        $service=Zsystem::service('group');
        $messageResponse=$service->hasDataTypeFromSlug($slug);
        if(!$messageResponse->code->status){
            return $messageResponse->response();
        }

        //然后才看这个对象是不是在这个组内
        $messageResponse=$service->hasObjects($objectId);
        if(!$messageResponse->code->status){
            return $messageResponse->response();
        }
        //最后看这个对象在这个组内携带了什么权限
        return $next($request);
    }
}