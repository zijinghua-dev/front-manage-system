<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;


use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Traits\Parameters;

class Authorize
{
//    use Parameters;
    public function handle($request, Closure $next)
    {
        //把userId写入request
        $data=$request->all();
        $data['userId']=Auth::user()->id;
        $request->replace($data);
        //用户如果是第一组成员，或者是第一组owner，执行一切动作
//        $repository=Zsystem::repository('datatype');
//        $userTypeId=$repository->key('user');
        $service=Zsystem::service('authorize');
        $messageResponse=$service->getPlatformOwnerGroup($data['slug']);
        if(!$messageResponse->code->status){
            if($messageResponse->getValidationResult()===false) {
                return $messageResponse->response();//参数出错
            }
        }
        $platformOwnerGroup=$messageResponse->data[0];
        $result=$service->inGroup(['slug'=>$data['slug'],'datatypeId'=>$data['datatypeId'],'datatypeSlug'=>'user',
            'id'=>$data['userId'],'groupId'=>$platformOwnerGroup->id]);
        if($result){
            return $next($request);
        }

        $messageResponse=$service->getPlatformAdminGroup($data['slug']);
        if(!$messageResponse->code->status){
            if($messageResponse->getValidationResult()===false) {
                return $messageResponse->response();//参数出错
            }
        }
        $platformAdminGroup=$messageResponse->data[0];
        $result=$service->inGroup(['slug'=>$data['slug'],'datatypeSlug'=>'user','id'=>$data['userId'],
            'datatypeId'=>$data['datatypeId'],'groupId'=>$platformAdminGroup->id]);
        if($result){
            $messageResponse=$service->shouldInGroupFamily(['slug'=>$data['slug'],'id'=>$data['id'],'groupId'=>$platformOwnerGroup->id]);
            if($messageResponse->code->status){
                return $messageResponse->response();//如果在第一组，那么就不能操作
            }else{
                //如果是参数错误，主要是groupId出错，则要中断进程
                if($messageResponse->getValidationResult()===false) {
                    return $messageResponse->response();
                }
            }
            return $next($request);//不在第一组，可以操作
        }

        //不是平台admin成员，就要通过查权限表来确定是否能操作
        $messageResponse=$service->checkNoAdminPermission($data);
        if(!$messageResponse->code->status){
                return $messageResponse->response();
        }
        return $next($request);


//        //参数准备
//        $groupId=null;
//        if(isset($request['groupId'])){
//            $groupId=$request['groupId'];
//        }
//        $objectId=null;
//        if(isset($request['id'])){
//            $objectId=$request['id'];
//        }
//        $actionId=null;
//        if(isset($request['actionId'])){
//            $actionId=$request['actionId'];
//        }
//        $destinationGroupId=null;
//        if(isset($request['destinationGroupId'])){
//            $destinationGroupId=$request['destinationGroupId'];
//        }
//        $dataTypeId=null;
//        if(isset($request['datatypeId'])){
//            $dataTypeId=$request['datatypeId'];
//        }
//        //如果是第二组成员，可以执行非第一组的任何操作
//        $service=Zsystem::service('authorize');
//        $result=$service->platformAdminCanDo(['userId'=>Auth::user()->id,'groupId'=>$groupId,'objectId'=>$objectId,
//            'dataTypeId'=>$dataTypeId,'destinationGroupId'=>$destinationGroupId]);
//        if($result->code->status){
//                return $next($request);
//        }
//
//        //检查当前对象ID，没有，报找不到当前对象的错，可以没有组，但总是有对象的啊
//        //这一个检查由另一个中间件完成，
//
//        //检查当前组ID，如果没有，操作对象只能是当前用户的owner，否则，报找不到当前组或者当前用户不拥有该对象的错
//        if(!isset($groupId)){
//            $result=$service->isOwn(['userId'=>Auth::user()->id,'dataTypeId'=>$dataTypeId,'objectId'=>$objectId]);
//            if($result->code->status){
//                return $next($request);
//            }
//        }
//
//        //检查当前组能否承载该类型对象，如果不能，报组不能容纳该类型对象的错
//        $messageResponse=$service->groupHasDatatype(['groupId'=>$groupId,'dataTypeId'=>$dataTypeId]);
//        if(!$messageResponse->code->status){
//            return $messageResponse->response();
//        }
//
//
//
//        //如果当前对象不是当前组，需要
//        //检查对象是否在该组内，不在，报当前组找不到当前对象的错
//
//        //检查当前组是否是当前对象的owner（顶点组没有onwer，默认是自己的owner），当前用户是否是当前组的onwer，如果是，允许一切操作
//        //检查该对象在该组内有没有指定操作方法，没有报该对象在该组不能操作该方法的错
//        //（创建对象时，要同时对可操作方法进行配置）
//
//        //获取该用户在该组内，各种角色所拥有的所有权限，能否操作该对象，如果可以，则执行
//        //检查该用户是否单独拥有有操作该对象的权限，如果不行，报权限不够的错
//
//        //如果是分享，还需要检查用户是否在目的组内，不在，报用户不在该组的错
//        //检查用户在该组内是否是onwer，如果是，允许一切操作
//        //获取该用户在该组内，各种角色所拥有的所有权限，能否分享操作（添加）该类型对象，如果可以，则执行
//        //
//        //如果是分享，会需要确认分享到目的组的各种可操作方法
//        //用户在目的组修改改对象的操作方法时，不能超过分享时的许可的操作方法
//
//        //解析出当前用户，检查权限
//        $user=Auth::guard('api')->user();
//        $service=Zsystem::service('authorize');
//        $parameters=['userId'=>$user->id,'groupId'=>$request['groupId'],'datatypeId'=>$request['datatypeId'],
//            'actionId'=>$request['actionId'],'slug'=>$request['slug'],'action'=>$request['action']];
//        $messageResponse = $service->checkPermission($parameters);
//        if($messageResponse->code->status){
//            return $next($request);
//        }else{
//            $response=$messageResponse->response();
//        }
//        return $response;
//        // 执行一些任务

    }
}