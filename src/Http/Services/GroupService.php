<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Responses\MessageResponse;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\GroupServiceInterface;

class GroupService extends BaseService implements GroupServiceInterface
{
    private $dataTypeId;//group本身也是一种datatype，这个ID
    public function __construct()
    {
        $repository=Zsystem::repository('dataType');
        $parameter['search'][]=['field'=>'slug','value'=>'groups'];
        $dataType=$repository->fetch($parameter);
        if(isset($dataType)){
            $this->dataTypeId=$dataType->id;
        }
    }

    public function inGroup($groupId,$userId,$dataTypeId,$objectId){

    }

    public function parent($groupId){
        $repository=Zsystem::repository('groupObject');
        $parameter['search'][]=['field'=>'object_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$this->dataTypeId,'filter'=>'=','algorithm'=>'and'];
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent;
        }
    }

    //这个组可以操作哪几种对象,这个需要由父组管理员配置，
    //需要父组本身有这些类型的对象，才能分配给子组
    public function dataTypes($groupId){
        $repository=Zsystem::repository('groupDataType');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $dataTypes=$repository->index($parameter);
        if(isset($dataTypes)){
            return $dataTypes->pluck('id');
        }
    }

    public function hasObjects($groupId,$parameters){
        if(isset($parameters['slug'])){
            $repository=Zsystem::repository('dataType');
            $dataTypeId=$repository->key($parameters['slug']);
        }elseif(isset($parameters['dataTypeId'])){
            $dataTypeId=$parameters['dataTypeId'];
        }
        if(!isset($dataTypeId)){
            throw new \Exception('group service 参数出错！');
        }
        $repository=Zsystem::repository('groupObject');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$dataTypeId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'object_id','value'=>$parameters['objectId'],'filter'=>'=','algorithm'=>'and'];
        $objects=$repository->fetch($parameter);
        if(!isset($objects)){
            $messageResponse=$this->messageResponse('group','search.failed');
        }else{
            $messageResponse=$this->messageResponse('group','search.success');
        }
        return $messageResponse;
    }

    public function hasDataType($groupId,$parameters){
        if(isset($parameters['slug'])){
            $repository=Zsystem::repository('dataType');
            $dataTypeId=$repository->key($parameters['slug']);
        }elseif(isset($parameters['dataTypeId'])){
            $dataTypeId=$parameters['dataTypeId'];
        }
        if(!isset($dataTypeId)){
            throw new \Exception('group service 参数出错！');
        }

        $repository=Zsystem::repository('groupDataType');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$dataTypeId,'filter'=>'=','algorithm'=>'and'];
        $dataType=$repository->fetch($parameter);
        if(!isset($dataType)){
            $messageResponse=$this->messageResponse('group','search.failed');
        }else{
            $messageResponse=$this->messageResponse('group','search.success');
        }
        return $messageResponse;
    }

}