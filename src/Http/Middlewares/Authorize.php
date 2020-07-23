<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;

class Authorize
{
    public function handle($request, Closure $next)
    {
        $slug=getSlug($request);
        //数据库里是复数形式，为什么啊！！！！！！！！
        $pslug=Str::plural($slug);
        $repository=Zsystem::repository('dataType');
        $parameter['search'][]=['field'=>'slug','value'=>$pslug];
        $slugId=$repository->fetch($parameter)->id;

        //先把action换成ID，show/read都对应是哪个ID
        list($class, $method) = explode('@', $request->route()->getActionName());
        $repository=Zsystem::repository('action');
        unset($parameter);
        $parameter['search'][]=['field'=>'name','value'=>$method];
        $parameter['search'][]=['field'=>'alias','value'=>$method];
        $actionId=$repository->fetch($parameter)->id;

        //当前组,前端传入的是uuid
        $groupId=$request['groupId'];
        //转换成id
        $repository=Zsystem::repository('group');
        unset($parameter);
        $parameter['search'][]=['field'=>'uuid','value'=>$groupId];
        $groupId=$repository->fetch($parameter)->id;

        //看一看用户还有那些组是当前组的父组，最高是哪个
        $user=Auth::guard('api')->user();
        $service=Zsystem::service('authorize');
        $parameters=['userId'=>$user->id,'groupId'=>$groupId,'slugId'=>$slugId,'actionId'=>$actionId,'slug'=>$slug,'action'=>$method];
        $messageResponse = $service->checkPermission($parameters);
        if($messageResponse->code->status){
            $response = $next($request);
        }else{
            $response=$messageResponse->response();
        }


        // 执行一些任务

        return $response;
    }
}