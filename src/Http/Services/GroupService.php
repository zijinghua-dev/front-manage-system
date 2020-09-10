<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Models\RestfulUser;
use Zijinghua\Zvoyager\Http\Models\User;

class GroupService extends BaseGroupService
{
    public function parent($parentId){
        $repository=Zsystem::repository('group');
        $parameter['search'][]=['field'=>'id','value'=>$parentId];
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent;
        }
    }

    public function parentId($childId){
        $repository=Zsystem::repository('group');
        $parameter['search'][]=['field'=>'id','value'=>$childId];
        $child=$repository->fetch($parameter);
        if(isset($child)){
            return $child->parent_id;
        }
    }

    //这个组可以操作哪几种对象,这个需要由父组管理员配置，
    //需要父组本身有这些类型的对象，才能分配给子组
    public function datatypes($groupId){
        $repository=Zsystem::repository('groupDatatype');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $datatypes=$repository->index($parameter);
        if(isset($datatypes)){
            return $datatypes->pluck('id');
        }
    }

    public function hasObjects($groupId,$parameters){
        if(isset($parameters['slug'])){
            $repository=Zsystem::repository('datatype');
            $datatypeId=$repository->key($parameters['slug']);
        }elseif(isset($parameters['datatypeId'])){
            $datatypeId=$parameters['datatypeId'];
        }
        if(!isset($datatypeId)){
            throw new \Exception('group service 参数出错！');
        }
        $repository=Zsystem::repository('groupObject');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'object_id','value'=>$parameters['objectId'],'filter'=>'=','algorithm'=>'and'];
        $objects=$repository->fetch($parameter);
        if(!isset($objects)){
            $messageResponse=$this->messageResponse('group','search.submit.failed');
        }else{
            $messageResponse=$this->messageResponse('group','search.submit.success');
        }
        return $messageResponse;
    }

    public function hasDatatype($groupId,$parameters){
        if(isset($parameters['slug'])){
            $repository=Zsystem::repository('datatype');
            $datatypeId=$repository->key($parameters['slug']);
        }elseif(isset($parameters['datatypeId'])){
            $datatypeId=$parameters['datatypeId'];
        }
        if(!isset($datatypeId)){
            throw new \Exception('group service 参数出错！');
        }

        $repository=Zsystem::repository('groupDatatype');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
        $datatype=$repository->fetch($parameter);
        if(!isset($datatype)){
            $messageResponse=$this->messageResponse('group','search.submit.failed');
        }else{
            $messageResponse=$this->messageResponse('group','search.submit.success');
        }
        return $messageResponse;
    }

    //对象添加到组内：groupObject内增加，
    public function append($parameters){
        $num=0;
        $group_id=$parameters['groupId'];
        $datatype_id=$parameters['datatypeId'];
        $repository=$this->repository('groupObject');
        if(is_array($parameters['id'])){
            foreach ($parameters['id'] as $key=>$id){
                $result=$repository->store(['group_id'=>$group_id,'datatype_id'=>$datatype_id,'object_id'=>$id]);
                if(isset($result)){
                    $num=$num+1;
                }
            }
        }else{
            $repository->store(['group_id'=>$group_id,'datatype_id'=>$datatype_id,'object_id'=>$parameters['id']]);
            $num=$num+1;
        }

        if($num>0){
            $messageResponse=$this->messageResponse($this->getSlug(),'append.submit.success',['num'=>$num]);
        }else{
            $messageResponse=$this->messageResponse($this->getSlug(),'append.submit.failed');
        }

        return $messageResponse;
    }

    public function expand($parameters){
        $repository=$this->repository($this->getSlug());
        $result=$repository->expand($parameters);
        if(isset($result)){
            $messageResponse=$this->messageResponse($this->getSlug(),'expand.submit.success');
        }else{
            $messageResponse=$this->messageResponse($this->getSlug(),'expand.submit.failed');
        }

        return $messageResponse;
    }

    public function shrink($parameters){
        $repository=$this->repository($this->getSlug());
        $result=$repository->shrink($parameters);
        if(isset($result)){
            $messageResponse=$this->messageResponse($this->getSlug(),'shrink.submit.success');
        }else{
            $messageResponse=$this->messageResponse($this->getSlug(),'shrink.submit.failed');
        }

        return $messageResponse;
    }

    //这是一个三元操作
    public function share($parameters){
        $repository=$this->repository($this->getSlug());
        $result=$repository->share($parameters);
        if(isset($result)){
            $messageResponse=$this->messageResponse($this->getSlug(),'share.submit.success');
        }else{
            $messageResponse=$this->messageResponse($this->getSlug(),'share.submit.failed');
        }

        return $messageResponse;
    }

    //删除一个组，不管要删除自己作为组的记录，还是删除自己作为对象的记录
    //如果仅仅指示删除本表，则采用remove
    public function delete($parameters){
        $data['groupId']=$parameters['id'];
        $data['datatypeId']=$parameters['datatypeId'];
        $data['objectId']=$parameters['id'];
        $data['id']=$parameters['id'];
        DB::beginTransaction();
        try {
            $groupIds=$data['groupId'];
            //找出所有子组
            $repository=$this->repository('groupFamily');
            $search['search'][]=['field'=>'group_id','value'=>$data['groupId'],'filter'=>'=','algorithm'=>'and'];
            $dataSet=$repository->index($search);
            if($dataSet->count()>0){
                $groupIds=array_merge($groupIds,$dataSet->pluck('child_id')->toArray());
            }

            //删除所有权限关联，全部组
            $repository=Zsystem::repository('groupObject');
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
            $num=$repository->delete($search);
            //删除group_object_permission
            $repository=Zsystem::repository('guop');
            $num=$num+$repository->delete($data);
            //删除group_role_permissions
            $repository=Zsystem::repository('objectAction');
            $num=$num+$repository->delete($data);
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'or'];
            $search['search'][]=['field'=>'parent_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'or'];
            $repository=Zsystem::repository('groupParent');
            $num=$num+$repository->delete($data);
            //删除组与子组关系的记录
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'or'];
            $search['search'][]=['field'=>'child_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'or'];
            $repository=Zsystem::repository('groupFamily');
            $num=$num+$repository->delete($data);

            //找出所有子组owner的对象:用户不应有owner组
            $repository=$this->repository('groupObject');
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
            $search['search'][]=['field'=>'owned','value'=>1,'filter'=>'=','algorithm'=>'and'];
            $dataSet=$repository->index($search);
            //删除所有对象
            //先转换一下数据集结构
            $objects=[];
            foreach ($dataSet as $key=>$item){
                $objects[$item['datatype_id']][]=$item['object_id'];
            }
            //删除数据对象本身
            foreach ($objects as $key=>$item){
                $repository=$this->repository('datatype');
                $datatype=$repository->show(['id'=>$key]);
                $slug=strtolower($datatype->slug);
                $repository=$this->repository($slug);
                $num=$num+$repository->destroy(['id'=>$item]);
            }
            //删除全部对象的权限关联
            foreach ($objects  as $key=>$item){
                unset($search);
                $repository=Zsystem::repository('groupObject');
                $search['search'][]=['field'=>'datatype_id','value'=>$key,'filter'=>'=','algorithm'=>'and'];
                $search['search'][]=['field'=>'object_id','value'=>$item,'filter'=>'in','algorithm'=>'and'];
                $num=$num+$repository->delete($search);

                $repository=Zsystem::repository('guop');
                unset($search);
                $search['search'][]=['field'=>'object_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
                $search['search'][]=['field'=>'datatype_id','value'=>$key,'filter'=>'=','algorithm'=>'and'];
                $num=$num+$repository->delete($search);
                //删除group_object_permission
                $repository=Zsystem::repository('objectAction');
                $num=$num+$repository->delete($data);
                //删除group_role_permissions
            }
            //删除作为组
            DB::commit();

            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.success');
            return $messageResponse;
        } catch (Exception $e) {
            DB::rollback();
//            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.failed');
            return $messageResponse;
        }

    }

    //返回创建的group记录,如果有父组，还要在groupObject中创建一条记录
    public function store($parameters)
    {
        //先创建组
        if($this->getSlug()!='group'){
            $this->setSlug('group');
        }
        $repository=$this->repository('group');
        unset($data);
        $data['datatype_id']=$parameters['datatypeId'];
        $data['object_id']=$parameters['objectId'];
        if(isset($parameters['userId'])){
            $data['owner_id']=$parameters['userId'];
        }
        if(isset($parameters['groupId'])){
            $data['owner_group_id']=$parameters['groupId'];
        }
//        if(isset($parameters['scheduleBegin'])){
//            $data['schedule_begin']=$parameters['scheduleBegin'];
//        }
//        if(isset($parameters['scheduleEnd'])){
//            $data['schedule_end']=$parameters['scheduleEnd'];
//        }
        $result=$repository->store($data);
        //如果有父组，还要把本组放进父组，更改groupFamily和groupParent
        if(isset($parameters['groupId'])){
            unset($data);
            $data=Arr::only($parameters,['datatypeId','objectId','groupId']);
            $data['childGroupId']=$result->id;
            $this->relationSave($data);
        }
        //
        return $result;
    }

    protected function relationSave($parameters){
        //如果有groupid，意味着有父组，还要再groupObject把本组放进父组里

            $repository=$this->repository('groupObject');
            unset($data);
            $data['group_id']=$parameters['groupId'];
            $data['datatype_id']=$parameters['datatypeId'];
            $data['object_id']=$parameters['objectId'];
            $data['child_group_id']=$parameters['childGroupId'];
            $data['owned']=1;
            $result=$repository->store($data);

        //groupParent和groupFamily是两个加速表，一个记录当前组的父组，一个记录当前组的子组
        $repository=$this->repository('groupParent');
        unset($data);
        $data['parent_id']=$parameters['groupId'];
        $data['group_id']=$parameters['childGroupId'];
        $result=$repository->store($data);
        $repository=$this->repository('groupFamily');
        unset($data);
        $data['group_id']=$parameters['groupId'];
        $data['child_id']=$parameters['childGroupId'];
        $result=$repository->store($data);
        return $result;
    }

    //有角色的用户可以浏览自己所在的组index,show
    public function index($parameters){
        //groupId为可选参数，如果没有groupId，则是该用户可操作的全部的group
        //如果传递了角色名称，则进一步筛选
        $ids=[];
        $search=null;
//        if(isset($parameters['role'])){
//            $repository=$this->repository('role');
//            $roleId=$repository->key($parameters['role']);
//
//            if(isset($roleId)){
//                $search['search'][]=['field'=>'role_id','value'=>$roleId,'filter'=>'=','algorithm'=>'and'];
//            }
//            if(isset($parameters['groupId'])){
//                $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
//            }
//            $repository=$this->repository('groupUserRole');
//            $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
//            $result=$repository->index($search);
//            if($result->count()==0){
//                $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.failed');
//                return $messageResponse;
//            }
//            $ids=array_unique($result->pluck('group_id')->toArray());
//            if(!$ids){
//                $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.failed');
//                return $messageResponse;
//            }
//            //这些组是需要的数据类型吗？
//            $repository=$this->repository($parameters['slug']);
//            unset($search);
//            $search['search'][]=['field'=>'group_id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
//            $result=$repository->index($search);
//            if($result->count()==0){
//                $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.failed');
//                return $messageResponse;
//            }
//            $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.success',$result);
//            return $messageResponse;
//        }

        //如果没有限制角色，则把所有的子组取出来
        if(isset($parameters['groupId'])){
            //有角色的这几个组，是不是当前组？或者是当前组的子组
            $repository=$this->repository('groupFamily');
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->index($search);
            if($result->count()>0){
                $ids=array_unique($result->pluck('child_id')->toArray());
                if(!$ids){
                    return null;
                }
            }
            $ids=array_merge($ids,[$parameters['groupId']]);
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
            $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            $repository=$this->repository('groupObject');
            $result=$repository->index($search);
            if($result->count()==0){
                $messageResponse=$this->messageResponse($parameters['slug'],'index.submit.failed');
                return $messageResponse;
            }
            $ids=$result->pluck('object_id')->toArray();
            unset($search);
            $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        }
        $repository=$this->repository($parameters['slug']);
        $result=$repository->index($search);
        $messageResponse=$this->messageResponse($parameters['slug'],'index.submit.success',$result);
        return $messageResponse;
    }

    //更改组的容量属性，可以装载哪些类型的对象
    public function tolerance($parameters){
        //输入参数ID(groupId),datatypeId,add:-1/1，增加或减少
        //最高不能超过父组的能力
    }

    public function getUserGroup(RestfulUser $user)
    {
        /* @var $repository \Zijinghua\Zvoyager\Http\Repositories\GroupRepository */
        $repository=$this->repository('group');
        return $repository->firstOrCreate($user);
    }
}
