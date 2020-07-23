<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class CheckGroup
{
    public function handle($request, Closure $next)
    {
        //show,update，delete这些操作都有操作对象，
        //要识别用户和这个对象是否在一个组内
        //用户的组：最高的，且互相不重复的
        //操作对象的组
        $slug=getSlug($request);
        $uuids=getUuids($request);
        //首先是这个组能否操作这个类型的对象
        $service=Zsystem::service('group');
        $messageResponse=$service->hasDataTypeFromSlug($slug);
        if(!$messageResponse->code->status){
            return $messageResponse->response();
        }

        //然后才看这个对象是不是在这个组内
        $messageResponse=$service->hasObjectsFromUuid($uuids);
        if(!$messageResponse->code->status){
            return $messageResponse->response();
        }
        //最后看这个对象在这个组内携带了什么权限
    }
}