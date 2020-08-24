<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Requests\StoreRequest;
use Zijinghua\Zbasement\Http\Responses\MessageResponse;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\GroupServiceInterface;

class GroupService extends BaseGroupService implements GroupServiceInterface
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

    public function inGroup($groupId,$userId,$datatypeId,$objectId){

    }

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

    public function store($parameters)
    {
        $data['name'] = $parameters['name'];
        $data['owner_id'] = $parameters['userId'];
        if(isset($parameters['groupId'])){
            $data['owner_group_id'] = $parameters['groupId'];
        }
        if(isset($parameters['picture'])){
            $data['picture']=$parameters['picture'];
        }
        if(isset($parameters['describe'])){
            $data['describe']=$parameters['describe'];
        }
        return parent::store($data);
    }

    public function index($parameters){
        if(isset($parameters['role'])){
            $repository=$this->repository('role');
            $roleId=$repository->key($parameters['role']);
        }
        $repository=$this->repository('groupUserRole');
        if(isset($roleId)){
            $search['search'][]=['field'=>'role_id','value'=>$roleId,'filter'=>'=','algorithm'=>'and'];
        }
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if(!isset($result)){
            $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.failed');
            return $messageResponse;
        }
        $ids=$result->pluck('group_id')->toArray();
        $repository=$this->repository('group');
        unset($search);
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $result=$repository->index($search);
        $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.success',$result);
        return $messageResponse;
    }



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