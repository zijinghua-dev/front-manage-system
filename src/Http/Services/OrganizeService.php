<?php


namespace Zijinghua\Zvoyager\Http\Services;



use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

use Zijinghua\Zbasement\Facades\Zsystem;


use Zijinghua\Zvoyager\Http\Contracts\OrganizeServiceInterface;

class OrganizeService extends GroupService implements OrganizeServiceInterface
{
    private $datatypeId;//group本身也是一种datatype，这个ID
    public function __construct()
    {
        $repository=Zsystem::repository('datatype');
        $parameter['search'][]=['field'=>'slug','value'=>'groups'];
        $datatype=$repository->fetch($parameter);
        if(isset($datatype)){
            $this->datatypeId=$datatype->id;
        }
    }

//    public function inGroup($groupId,$userId,$datatypeId,$objectId){
//
//    }

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

    public function create($parameters){
        $data['name'] = $parameters['name'];
        if(isset($parameters['picture'])){
            $data['picture']=$parameters['picture'];
        }
        if(isset($parameters['describe'])){
            $data['describe']=$parameters['describe'];
        }
        //创建organize本身
        $repository=$this->repository('organize');
        $result=$repository->store($data);
        unset($data);
        //不是个人组，不记录userId，ownerId为null
        $data=Arr::only($parameters,['groupId','datatypeId']);
        $data['objectId']=$result->id;
        $group= parent::store($data);//保存group和family
        //重新将获得的groupID保存到organize里
        $repository=$this->repository('organize');
        $repository->update(['id'=>$result->id,'group_id'=>$group->id]);
        $result->group_id=$group->id;
        return $result;
    }

    //如果一个普通用户有权创建组，父组应当是当前组，创建完毕后，应当把用户加到当前组，给当前用户赋予默认的owner角色，
    //这样，用户能够继续给组添加角色，添加权限，添加用户
    //个人组内有一个owner子组，如果用户被授予创建某种对象，放在这个子组里
    public function store($parameters)
    {
        DB::beginTransaction();
        try {
            //首先是创建organize本身，以及organize映射的组
            $result=$this->create($parameters);
            //如果当前用户不是平台owner和admin
            $service=Zsystem::service('authorize');
            if(!$service->isPlatformOwner($parameters['userId'])&&!$service->isPlatformAdmin($parameters['userId'])){
                $data=['groupId'=>$result->group_id,'userId'=>$parameters['userId']];
                parent::addUserToGroup($data);//添加用户到组内
                $data=['groupId'=>$result->group_id,'userId'=>$parameters['userId']];
                parent::authorizeOwner($data);//给用户配置默认所有人角色
                $data=['userId'=>$parameters['userId'],'datatypeId'=>$parameters['datatypeId'],'objectId'=>$result->id];
                //因为当前用户是创建者，拥有index权限，所以将该组添加到该用户的个人组；其他地方应当判断是否有index权限，没有权限，不能添加到个人组
                parent::addObjectToPersonalGroup($data);//把刚刚创建的组添加到用户的个人组，方便用户索引；
//                parent::addObjectToPersonalOwnerGroup($data);
            }
            DB::commit();

            $messageResponse=$this->messageResponse($this->getSlug(),'store.submit.success',$result);
            return $messageResponse;
        } catch (Exception $e) {
            DB::rollback();
//            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }
        $messageResponse=$this->messageResponse($this->getSlug(),'store.submit.failed');
        return $messageResponse;
    }

    //仅供系统内部调用，用于创建顶级组。比如，用户缴费后，触发本方法，自动创建一个顶级组
    //顶级组需要连带创建几个模板角色，角色，权限要放入该顶级组
    //不太容易让平台管理员创建顶级组，因为要配置owner角色，需要传入userId，平台管理员创建之后，要转让给其他用户
    public function createTopGroup($parameters)
    {
        $result=$this->create($parameters);
        //如果当前用户不是平台owner和admin
        parent::addTemplateRoleToGroup($data);
        parent::addPermissionToGroup($data);
            $data=['groupId'=>$result->group_id,'userId'=>$parameters['userId']];
            parent::addUserToGroup($data);//添加用户到组内
            $data=['groupId'=>$result->group_id,'userId'=>$parameters['userId']];
            parent::authorizeOwner($data);//给用户配置默认所有人角色
            $data=['userId'=>$parameters['userId'],'datatypeId'=>$parameters['datatypeId'],'objectId'=>$result->id];
            //因为当前用户是创建者，拥有index权限，所以将该组添加到该用户的个人组；其他地方应当判断是否有index权限，没有权限，不能添加到个人组
            parent::addObjectToPersonalGroup($data);//把刚刚创建的组添加到用户的个人组，方便用户索引；
    }
//    protected function addUserToGroup($parameters){
//        $repository=$this->repository('datatype');
//        $userDatatypeId=$repository->key('user');
//        $repository=$this->repository('groupObject');
//        $result=$repository->store(['datatype_id'=>$userDatatypeId,'object_id'=>$parameters['userId'],'group_id'=>$parameters['groupId']]);
//    }





//    public function expand($parameters){
//        $model=$this->model('groupDatatype');
//        $data=[];
//        if(is_array($parameters['datatypeId'])){
//            foreach ($parameters['datatypeId'] as $key=>$datatypeId){
//                $data[]=['group_id'=>$parameters['groupId'],'datatype_id'=>$datatypeId];
//            }
//        }else{
//            $data[]=['group_id'=>$parameters['groupId'],'datatype_id'=>$parameters['datatypeId']];
//        }
//        try {
//            return $model->insert($data);
//        } catch (QueryException $e) {
//            $errorCode = $e->errorInfo[1];
//            if($errorCode == 1062){
//                return;
//            }else{
//                throw $e;
//            }
//        }
//        //这里会有数据库唯一索引冲突报错
//    }

//    //当删除一个组的属性时，各个关联表都要去除对应记录
//    public function shrink($parameters){
//        //groupId,datatypeId转为数据库字段名
//        $data['group_id']=$parameters['groupId'];
//        $data['datatype_id']=$parameters['datatypeId'];
//        DB::beginTransaction();
//        try {
//            $repository=Zsystem::repository('groupDatatype');
//            $num=$repository->remove($data);
//            ////删除group_object
//            $repository=Zsystem::repository('groupObject');
//            $num=$num+$repository->remove($data);
//            //删除group_object_permission
//            $repository=Zsystem::repository('groupObjectPermission');
//            $num=$num+$repository->remove($data);
//            //删除group_role_permissions
//            $repository=Zsystem::repository('groupRolePermission');
//            $num=$num+$repository->remove($data);
//            //删除group_user_permissions
//            $repository=Zsystem::repository('groupUserPermission');
//            $num=$num+$repository->remove($data);
//
//            DB::commit();
//            return $num;
//        } catch (Exception $e) {
//            DB::rollback();
//            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
//        }
//    }

}