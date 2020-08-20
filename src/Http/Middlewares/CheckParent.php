<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class CheckParent
{
    public function handle($request, Closure $next)
    {
        //如果是更改组的属性，增减datatype类型，要检查父组是否拥有对应的datatype
        if(!isset($request['groupId'])||empty($request['groupId'])){
            return $next($request);
        }
        if(!isset($request['datatypeId'])||empty($request['datatypeId'])){
            throw new \Exception('必须传递datatypeId');
        }
        $service=Zsystem::service('group');
        $parentId=$service->parentId($request['groupId']);
        //如果不是1号组，必然有父组
        if(($request['groupId']!=1)&&(!isset($parentId))){
            throw new \Exception('这个group出错了！');
            }
        $response=$service->hasDatatype($parentId,['datatypeId'=>$request['datatypeId']]);
        if($response->code->status){
            return $next($request);
        }else{
            //要返回这个组不能添加这个属性的信息和code
            return $response->response();
        }
    }
}