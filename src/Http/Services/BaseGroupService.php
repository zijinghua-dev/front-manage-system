<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Facades\DB;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;

class BaseGroupService extends BaseService
{
    //我的数据对象
    public function mine($parameters){
        //找到当前用户的所有组，group_objects是组直接表，一个组里own了哪些对象；group_user_roles是间接表
        //user_objects是个人直接表，own了哪些对象
        //group_user_object_permissions是在组里一对一授权
        //首先看group_objects
        $ids=[];
        $repository=$this->repository('groupObject');
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $ids=$result->pluck('object_id')->toArray();
        }
        //group_user_object_permissions有数据吗?用户并不拥有某个对象，但可能拥有某种权限
        $repository=$this->repository('guop');
        $result=$repository->index($search);
        if($result->count()>0){
            $ids=array_merge($ids,$result->pluck('object_id')->toArray());
        }
        //再看这个用户own了哪些对象
        $repository=$this->repository($this->getSlug());
        unset($search);
        $search['search'][]=['field'=>'owner_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $ids=array_merge($ids,$result->pluck('id')->toArray());
        }

        //最后看这个用户在哪些组里有角色
        $repository=$this->repository('groupUserRole');
        unset($search);
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $groupIds=$result->pluck('group_id')->toArray();
            $gurIds=$result->pluck('id')->toArray();
        }
        if($this->getSlug()=='group'){
            $ids= array_merge($ids,$groupIds);
        }
        //这些角色可以操作本对象类型吗？
        $repository=$this->repository('groupRolePermission');
        unset($search);
        $search['search'][]=['field'=>'gur_id','value'=>$gurIds,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $groupIds=$result->pluck('group_id')->toArray();
        }
        //这些组里有这个类型的对象吗？
        $repository=$this->repository('groupObject');
        unset($search);
        $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $objectIds=$result->pluck('object_id')->toArray();
            $ids= array_merge($ids,$objectIds);
        }

        if(emptyObjectOrArray($ids)){
            $messageResponse=$this->messageResponse($this->getSlug(),'mine.submit.failed');
            return $messageResponse;
        }
        $repository=$this->repository($this->getSlug());
        unset($search);
        $search['search'][]=['field'=>'id','value'=>array_unique($ids),'filter'=>'in','algorithm'=>'and'];
        $result=$repository->index($search);
        $messageResponse=$this->messageResponse($this->getSlug(),'mine.submit.success',$result);
        return $messageResponse;
    }

    public function clear($parameters){
        //组内移除，并不删除
        //必须通过group_objects model来做
        //通过事务，删除关联记录
        //组内移除需要将uuid变成 object_id，
        $groupId=$parameters['groupId'];
        $id=$parameters['id'];

        DB::beginTransaction();
        try {
            //删除group_object,group_user_object_permissions,object_abilities
            $repository=$this->repository('groupObject');
            $parameters= ['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
            $parameters= ['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            $parameters= ['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
            $repository->delete($parameters);

            //删除group_object_permission
            $repository=$this->repository('guop');
            $repository->delete($parameters);

            //删除group_role_permissions
            $repository=$this->repository('objectAction');
            $repository->delete($parameters);



            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }
    }
}