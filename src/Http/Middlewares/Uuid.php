<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Zijinghua\Zbasement\Facades\Zsystem;

class Uuid
{
    public function handle($request, Closure $next)
    {
        //输入参数里必须有ID，没有ID则必须有uuid，否则报错
        $service=Zsystem::service('parameter');
        $messageResponse=$service->hasId($request);
        if(!$messageResponse->code->status){
            //改成field=》，value=》格式
            return $messageResponse->response();
        }

        //把输入参数里的uuid转成datatypeId+ObjectId
        //如果uuid不对，要报错，允许没有uuid，因为有id啊
        if($messageResponse->data[0]['field']=='uuid'){
            $service=Zsystem::service('user');
            $messageResponse=$service->transferKey($request);
            if(!$messageResponse->code->status){
                return $messageResponse->response();
            }
            $id=$messageResponse->data[0];
        }else{
            $id=$messageResponse->data[0]['value'];
        }

        if(is_array($id)){
            $id=array_unique($id);
        }
        $data = $request->all();
        $service=Zsystem::service('parameter');
        $data=$service->replaceId($data,$id);
//        if(!$messageResponse->code->status) {
//            return $messageResponse->response();//格式错误，没有携带可用的id
//        }
        $request->replace($data);
        return $next($request);
//        if(isset($request['uuid'])){
//            $uuid=$request['uuid'];
//        }else{
//            if(!isset($request['id'])){
//
//
//            }
//
//            if(!isset($request['search'])){
//                return $next($request);
//            }
//            foreach ($request['search'] as $key=>$item){
//                if($item['field']=='uuid'){
//                    $uuid=$item['value'];
//                }
//            }
//        }
//        if(!isset($uuid)){
//            return $next($request);
//        }
//        $repository=Zsystem::repository('user');
//        $id=$repository->transferKey($uuid);//输出null，没有；输出false，出错了。
//
//        //如果是输入参数是数组，则遍历转换
//        //如果输入输出数据不一致，则退回报错
//        if(!emptyObjectOrArray($id)){
//            $data = $request->all();
//            if(count($id)==1){
//                $id=$id[0];
//            }
//            if(isset($data['search'])){
//                if($data['search']['field']=='uuid'){
//                    $data['search']['field']=='id';
//                    $data['search']['value']==$id;
//                }
//            }else{
//                if(isset($data['uuid'])) {
//                    unset($data['uuid']);
//                }
//                $data['id']=$id;
//            }
//            $request->replace($data);
//        }

//        return $next($request);
    }
}