<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\GroupRepositoryInterface;

class GroupRepository extends BaseRepository implements GroupRepositoryInterface
{
    public function key($name){
        if(is_array($name)){
            $parameter=$name;
        }else{
            $parameter['search'][]=['field'=>'name','value'=>$name];
        }

        return parent::key($parameter);
    }

    //删除一个组，需要级联删除多个表
    public function delete($parameters){
        //批量删除
        $parameters=$this->getIndexParameter($parameters);

        $model=$this->find($parameters);
        return $this->softDelete($model);

    }

    protected function deleteRelation($parameters){
        $model=Zsystem::model('groupDataType');

    }
    public function destroy($parameters){
        //单一删除
        $parameters=$this->getIndexParameter($parameters);

        $model=$this->find($parameters);
        return $this->softDelete($model);

    }
}