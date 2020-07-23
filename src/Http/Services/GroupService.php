<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
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
        $parameter['search'][]=['field'=>'object_id','value'=>$groupId,'filter'=>'=','algothm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$this->dataTypeId,'filter'=>'=','algothm'=>'and'];
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent;
        }
    }

    //这个组可以操作哪几种对象,这个需要由父组管理员配置，
    //需要父组本身有这些类型的对象，才能分配给子组
    public function dataTypes($groupId){
        $repository=Zsystem::repository('groupDataType');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algothm'=>'and'];
        $dataTypes=$repository->index($parameter);
        if(isset($dataTypes)){
            return $dataTypes->pluck('id');
        }
    }

    public function hasDataType($groupId,$dataTypeId){
        $repository=Zsystem::repository('groupDataType');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algothm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$dataTypeId,'filter'=>'=','algothm'=>'and'];
        $dataType=$repository->fetch($parameter);
        if(isset($dataType)){
            return true;
        }
        return false;
    }
    public function hasDataTypeFromSlug($groupId,$slug){
        //$slug先转成复数形式
        $pslug=Str::plural($slug);
        $repository=Zsystem::repository('dataType');
        $parameter['search'][]=['field'=>'slug','value'=>$pslug];
        $dataType=$repository->fetch($parameter);
        if(!isset($dataType)){
            return false;
        }

        unset($parameter);
        $repository=Zsystem::repository('groupDataType');
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algothm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$dataType->Id,'filter'=>'=','algothm'=>'and'];
        $dataType=$repository->fetch($parameter);
        if(isset($dataType)){
            return true;
        }
        return false;
    }

}