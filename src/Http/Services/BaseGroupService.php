<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Facades\DB;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;

class BaseGroupService extends BaseService
{
    //我的数据对象，包括
    //1、自己创建的
    //2、别人分享给我的
    //不包括：组owner的
    public function mine($parameters){
        //首先把别人分享，或者自己分享出来的对象取出
        $objectIds=[];
        $repository=$this->repository('guop');
        $search['paginate']=0;
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $objectIds=$result->pluck('object_id')->toArray();
        }
        //然后把个人创建的对象，以及分享的对象合并取出
        $repository=$this->repositoryById($parameters['datatypeId']);
        unset($search);
        $search['search'][]=['field'=>'owner_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'id','value'=>$objectIds,'filter'=>'in','algorithm'=>'or'];
        $dataset=$repository->index($search);
        $messageResponse=$this->messageResponse($this->getSlug(),'mine.submit.success',$dataset);
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

    public function store($parameters)
    {
        $parameters['owner_group_id']=$parameters['groupId'];
        $parameters['owner_id']=$parameters['userId'];
        return parent::store($parameters);
    }

    //把数据对象添加到组里
    public function add($parameters)
    {
        $repository=$this->repository('groupObject');
        $result=$repository->save(['group_id'=>$parameters['groupId'],'datatype_id'=>$parameters['datatypeId'],
            'object_id'=>$parameters['id']]);
        $messageResponse=$this->messageResponse($this->getSlug(),'add.submit.success', $result);
        return $messageResponse;
    }
}