<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\GroupServiceInterface;

class GroupService extends BaseService implements GroupServiceInterface
{
    private $dataTypeId;
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
        $repository=Zsystem::repository('groupDataType');
        $parameter['search'][]=['field'=>'object_id','value'=>$groupId,'filter'=>'=','algothm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$this->dataTypeId,'filter'=>'=','algothm'=>'and'];
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent;
        }
    }
}