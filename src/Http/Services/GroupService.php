<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Responses\MessageResponse;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\GroupServiceInterface;

class GroupService extends BaseService implements GroupServiceInterface
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
}