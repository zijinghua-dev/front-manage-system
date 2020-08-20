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
        $search['paginate']=0;
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $ids=$result->pluck('object_id')->toArray();//组own的，和分享到组的都在这里
        }
        //group_user_object_permissions有数据吗?用户并不拥有某个对象，但可能拥有某种权限
        $repository=$this->repository('guop');
        $result=$repository->index($search);
        if($result->count()>0){
            $ids=array_merge($ids,$result->pluck('object_id')->toArray());//个人分享的在这里
        }
        //再看这个用户own了哪些对象
        $repository=$this->repository($this->getSlug());
        unset($search);
        $search['paginate']=0;
        $search['search'][]=['field'=>'owner_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $ids=array_merge($ids,$result->pluck('id')->toArray());
        }

        //最后看这个用户在哪些组里有角色
        $repository=$this->repository('groupUserRole');
        unset($search);
        $search['paginate']=0;
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $groupIds=$result->pluck('group_id')->toArray();
            if($this->getSlug()=='group'){
                $ids= array_merge($ids,$groupIds);
            }
            $roleIds=$result->pluck('role_id')->toArray();
        }

        //这些角色可以操作本对象类型吗？
        if(isset($groupIds)){
            $repository=$this->repository('groupRolePermission');
            unset($search);
            $search['paginate']=0;
            $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
            $search['search'][]=['field'=>'role_id','value'=>$roleIds,'filter'=>'in','algorithm'=>'and'];
            $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->index($search);
            if($result->count()>0){
                $groupIds=$result->pluck('group_id')->toArray();
            }
            //这些组里有这个类型的对象吗？
            $repository=$this->repository('groupObject');
            unset($search);
            $search['paginate']=0;
            $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
            $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->index($search);
            if($result->count()>0){
                $objectIds=$result->pluck('object_id')->toArray();
                $ids= array_merge($ids,$objectIds);
            }
        }


        if(emptyObjectOrArray($ids)){
            $messageResponse=$this->messageResponse($this->getSlug(),'mine.submit.success');
            return $messageResponse;
        }
        $repository=$this->repository($this->getSlug());
        unset($search);
        $search['search'][]=['field'=>'id','value'=>array_unique($ids),'filter'=>'in','algorithm'=>'and'];
        $result=$repository->index($search);
        $messageResponse=$this->messageResponse($this->getSlug(),'mine.submit.success',$result);
        return $messageResponse;
    }

    //clear和delete的调用，外包事务，返回response，不要直接调用
    protected function clear($parameters){
        //组内移除，并不删除
        //必须通过group_objects model来做
        //通过事务，删除关联记录
        //组内移除需要将uuid变成 object_id，
        $groupId=$parameters['groupId'];
        $id=$parameters['id'];

//        DB::beginTransaction();
//        try {
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



//            DB::commit();
//            return true;
//        } catch (Exception $e) {
//            DB::rollback();
//            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
//        }
    }

    //删除本身记录，并同时删除关联数据
    protected function remove($parameters){
        //除了删除当前对象，还要把关联权限全部删除
        //批量删除
        $repository=$this->repository($this->getSlug());
        $num=$repository->delete(['id'=>$parameters['id']]);
        //删除关联表
        $data['datatype_id']=$parameters['datatypeId'];
        $data['object_id']=$parameters['id'];
        ////删除group_object
        $repository=Zsystem::repository('groupObject');
        $num=$num+$repository->delete($data);
        //删除group_object_permission
        $repository=Zsystem::repository('guop');
        $num=$num+$repository->delete($data);
        //删除group_role_permissions
        $repository=Zsystem::repository('objectAction');
        $num=$num+$repository->delete($data);
        //删除group_user_permissions
//        $repository=Zsystem::repository('userObject');
//        $num=$num+$repository->delete($data);
        //调用者如果是用户的话，还要删除所有自己own的对象
        //调用者如果是组的话，还要删除所有自己own的对象；凡是不能被关联删除的表，应当注明，并自行维护关联数据，在关联表数据被删除后，还能保持自身不出错
        return $num;
    }

    //返回结果：1，没有这个字段，false；2，没有值，null
    public function index($parameters){
        //组内groupObject
        $groupId=$parameters['groupId'];
        $datatypeId=$parameters['datatypeId'];
        $repository=$this->repository('groupObject');
        $search['paginate']=0;//0表示获取全部数据，不分页
        $search['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
        $dataSet=$repository->index($search);
        if($dataSet->count()==0){
            $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.success');
            return $messageResponse;
        }
        $ids=$dataSet->pluck('object_id')->toArray();
        $repository=$this->repository($this->getSlug());
        unset($search);
//        $search['paginate']=0;//0表示获取全部数据，不分页
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $dataSet=$repository->index($search);
        if($dataSet->count()==0){
            $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.success');
            return $messageResponse;
        }
        //测试代码---------------------------------
//        $result=UserResource::collection($result);
        //测试代码结束---------------------------------
        //如果$result为null或空，那么意味着刚刚删除掉这个数据，应该报异常
//        $code='zbasement.code.'.$this->slug.'.index.success';
        $resource=$this->getResource($this->getSlug(),'index');
        $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.success', $dataSet,$resource);
        return $messageResponse;
    }
}