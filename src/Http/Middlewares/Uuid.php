<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class Uuid
{
    public function handle($request, Closure $next)
    {
        //把输入参数里的uuid转成datatypeId+ObjectId

        if(isset($request['uuid'])){
            $uuid=$request['uuid'];
        }else{
            if(!isset($request['search'])){
                return $next($request);
            }
            foreach ($request['search'] as $key=>$item){
                if($item['field']=='uuid'){
                    $uuid=$item['value'];
                }
            }
        }
        if(!isset($uuid)){
            return $next($request);
        }
        $repository=Zsystem::repository('user');
        $result=$repository->key($uuid);//输出null，没有；输出false，出错了。

        //如果是输入参数是数组，则遍历转换
        //如果输入输出数据不一致，则退回报错
    }
}