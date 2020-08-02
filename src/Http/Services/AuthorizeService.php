<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\AuthorizeServiceInterface;

class AuthorizeService extends BaseService implements AuthorizeServiceInterface
{
    protected function groupPermissionParameter($userId,$groupId){
        //输出一个数组，便于eloquent和cache/redis都能够使用
        //如果是redis，他会自动序列化，并不需要我们进行处理
    }

    //查找该组的父组，同时查看该用户是否在父组内
    //组的父组是不能有中断的

    protected function parentSearch($userId,$groupId){
        $parameter['search'][]=['field'=>'user_id','value'=>$userId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $repository=Zsystem::repository('groupDataType');
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent->id;
        }
    }

    protected function addPermission($parentPermissions,$permissions){
        foreach ($parentPermissions as $key=>$item){
            $result=$permissions->where('datatype_id',$item['datatype_id'])->where('action_id',$item['action_id']);
            if($result->count()==0){
                $permissions->push($item);
            }
        }
        return $permissions;
    }

    protected function permissions($parameters){
//获取该用户在该组的permissions
        $parameter['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $repository=Zsystem::repository('groupUserPermission');
        $permissions=$repository->index($parameter);
        return $permissions;
    }

    public function checkAdminPermission($parameters){
        //如果组ID为1，只检查该用户是否在该组内，不用检查对象是否在该组内
        //如果组ID为2，只检查该用户是否在该组内，以及操作对象是否是在1组内，只要不是1组成员，都可以操作
        $userService=Zsystem::service('user');
        $groupIds=$userService->groupIds($parameters['userId']);
        if(in_array(1,$groupIds)){
            return true;
        }elseif(in_array(2,$groupIds)){
           //现在要检查操作对象是不是1组的
            if($parameters['groupId']>1){
                return true;
            }
        }
        return false;
    }

    public function checkUserPermission($parameters){
        //改变组的属性，想要添加datatype的时候，必须父组有这个datatype
        $groupService=Zsystem::service('group');
        $parent=true;
        while (isset($parent)){
            //首先是当前组的权限
            $permissions=$this->permissions($parameters);
            //再找父组
            //查看有没有权限，没有继续找
            $result=$permissions->where('action_id',$parameters['actionId'])->where('datatype_id',$parameters['dataTypeId']);
            if($result->count()>0){

                return true;
            }

            //现在查看有没有父组
            $parent=$groupService->parent($parameters['groupId']);
            if(isset($parent)){
                //把当前组改成父组
                $parameters['groupId']=$parent->group_id;
            }
        }
        return false;
    }

    public function checkPermission($parameters){
        //如果组ID为null，检查是不是该用户自己创建的对象
        //如果组ID为1，只检查该用户是否在该组内，不用检查对象是否在该组内
        //如果组ID为2，只检查该用户是否在该组内，以及操作对象是否是在1组内，只要不是1组成员，都可以操作
//        $userId,$groupId,$dataTypeId,$objectId,$actionId
        $result=$this->checkAdminPermission($parameters);
        if($result){
            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.success');
            return $messageResponse;
        }
        $result=$this->checkUserPermission($parameters);
        if($result){
            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.success');
            return $messageResponse;
        }

            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.failed');
            return $messageResponse;

    }

    public function inPlatformOwner($parameters){
        //第一组，第二组必然存在，丢失则报错
            //查看该用户是第一组的owner还是成员
            $repository=Zsystem::repository('group');
            $group=$repository->get(0,1);
            if(!isset($group)){
                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
                return $messageResponse;
            }
            if($group->count()==0){
                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
                return $messageResponse;
            }
            if($group[0]->owner_id==$parameters['userId']){
                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');
                return $messageResponse;
            }
            //找出user的类型
            $repository=Zsystem::repository('datatype');
            $userTypeId=$repository->key('user');
            //查看是不是组成员
            $repository=Zsystem::repository('groupObject');
            $search['search'][]=['field'=>'datatype_id','value'=>$userTypeId,'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'object_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'group_id','value'=>$group[0]->id,'filter'=>'=','algorithm'=>'and'];
            $result=$repository->fetch($search);
            if(emptyObjectOrArray($result)){
                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.failed');
                return $messageResponse;
            }
//            if($result->count()==0){
//                return false;
//            }
//            if($result->where('object_id',$parameters['userId']))
            //查看组成员是否拥有这个数据对象
        $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');
        return $messageResponse;
//        }
    }

    public function platformAdminCanDo($parameters){
        //没有第一第二组，报错
        $repository=Zsystem::repository('group');
        $groups=$repository->get(0,2);
        if(isset($groups)){
            if($groups->count()<2){
                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
                return $messageResponse;
            }
            if($groups[0]->id==$parameters['groupId']){
                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
                return $messageResponse;
            }
        }

        //当前用户是在第二组吗？不在第二组不能操作
        $repository=Zsystem::repository('datatype');
        $userTypeId=$repository->key('user');
        $groupId=$groups[1]->id;
        $repository=Zsystem::repository('groupObject');
        $search['search'][]=['field'=>'datatype_id','value'=>$userTypeId,'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'object_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(!isset($result)){
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.failed');
            return $messageResponse;
        }
        //当前操作对象是不是被第一组owner？或者是仅仅被第一组成员所onwer吗？
        //个人owner和组owner，如果有组owner，则无视个人owner，
        $slug=$parameters['slug'];
        $repository=Zsystem::repository($slug);
        $id=$parameters['id'];
        if(is_array($id)){
            foreach ($id as $key=>$item){
                $search['search'][]=['field'=>id,'value'=>$item,'filter'=>'=','algorithm'=>'or'];
            }
        }else{
            $search['search'][]=['field'=>id,'value'=>$parameters['id'],'filter'=>'=','algorithm'=>'or'];
        }

        $result=$repository->index($search);
        if(!isset($result)){
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
            return $messageResponse;//操作对象必须存在
        }
        if($result->count()==0){
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
            return $messageResponse;//操作对象必须存在
        }
        //找出操作对象的owner
        $owner_ids=$result->pluck('owner_id')->toArray();
        foreach ($result as $key=>$item){
            if(isset($item->owner))
        }
        //找出第一组的成员ID

        $result=$repository->index($search);
        if($result->count()>0){
            $memberIds=$result->pluck('id')->toArray();
        }
        //操作对象的owner
        $parameters['userId'];
        'groupId'=>$groupId,'objectId'=>$objectId,
            'dataTypeId'=>$dataTypeId,'destinationGroupId'=>$destinationGroupId]
    }
}