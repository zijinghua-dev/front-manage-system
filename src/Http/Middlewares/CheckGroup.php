<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class CheckGroup
{
    public function handle($request, Closure $next)
    {
        //检查这个组可以操作哪些对象——允许所有动作
        //检查这个组内有没有这个对象
//        $slug=getSlug($request);
//        $repository=Zsystem::repository('dataType');
//        $dataTypeId=$repository->key($slug);
//        $uuids=getUuids($request);

        //如果组ID为空，意味着用户对自己创建的对象进行操作
        //由authorize中间件检查这些对象是否该用户的owner
        //获得组的UUID，需要转换成id

        if(!isset($request['groupId'])||empty($request['groupId'])){
            return $next($request);
        }

        //首先是这个组能否操作这个类型的对象
        $service=Zsystem::service('group');
        $messageResponse=$service->hasDataType($request['groupId'],['dataTypeId'=>$request['dataTypeId']]);
        if(!$messageResponse->code->status){
            return $messageResponse->response();
        }

        //然后才看这个对象是不是在这个组内
        $messageResponse=$service->hasObjects($request['groupId'],['dataTypeId'=>$request['dataTypeId'],'objectId'=>$request['objectId']]);
        if(!$messageResponse->code->status){
            return $messageResponse->response();
        }
        //最后看这个对象在这个组内携带了什么权限
        return $next($request);
    }
}