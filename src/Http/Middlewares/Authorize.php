<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Traits\Parameters;

class Authorize
{
    use Parameters;
    public function handle($request, Closure $next)
    {
        //获取该用户在该组内，拥有的所有权限
        //检查该用户是否有操作该对象的权限

        //准备各种查询参数
        //首先是slugId

        //解析出当前用户，检查权限
        $user=Auth::guard('api')->user();
        $service=Zsystem::service('authorize');
        $parameters=['userId'=>$user->id,'groupId'=>$request['groupId'],'datatypeId'=>$request['datatypeId'],
            'actionId'=>$request['actionId'],'slug'=>$request['slug'],'action'=>$request['action']];
        $messageResponse = $service->checkPermission($parameters);
        if($messageResponse->code->status){
            return $next($request);
        }else{
            $response=$messageResponse->response();
        }
        return $response;
        // 执行一些任务

    }
}