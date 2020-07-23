<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Http\Exceptions\HttpResponseException;
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
        $parameter['search'][]=['field'=>'user_id','value'=>$userId,'filter'=>'=','algothm'=>'and'];
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algothm'=>'and'];
        $repository=Zsystem::repository('groupDataType');
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent->id;
        }
    }

    protected function addPermission($parentPermissions,$permissions){
        foreach ($parentPermissions as $key=>$item){
            $result=$permissions->where('slug_id',$item['slug_id'])->where('action_id',$item['action_id']);
            if($result->count()==0){
                $permissions->push($item);
            }
        }
        return $permissions;
    }

    protected function permissions($parameters){
//获取该用户在该组的permissions
        $parameter['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algothm'=>'and'];
        $parameter['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algothm'=>'and'];
        $repository=Zsystem::repository('groupPermission');
        $permissions=$repository->index($parameter);
        return $permissions;
    }

    public function checkPermission($parameters){
//        $userId,$groupId,$dataTypeId,$objectId,$actionId

        $parentGroupId=0;
        while (isset($parentGroupId)){
            //首先是当前组的权限
            $permissions=$this->permissions($parameters);
            //再找父组
            $groupService=Zsystem::service('group');
            $parentGroup=$groupService->parent($parameters['groupId']);
            if(!isset($parentGroup)){
                break;
            }
            $parentGroupId=$parentGroup->group_id;
            //父组的权限
            $parameters['groupId']=$parentGroupId;
            $parentPermissions=$this->permissions($parameters);
                //合并
            //添加permission
            $permissions=$this->addPermission($parentPermissions,$permissions);
            //查看有没有权限，没有继续找
            $result=$permissions->where('action_id',$parameters['actionId'])->where('slug_id',$parameters['slugId']);
            if($result->count()>0){
                $messageResponse=$this->messageResponse($parameters['slug'],'authorize.success');
                return $messageResponse;
            }
        }

            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.failed');
            return $messageResponse;

    }
}