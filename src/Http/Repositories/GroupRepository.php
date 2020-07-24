<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


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
            $parameter['search'][]=['field'=>'uuid','value'=>$name];
        }

        return parent::key($parameter);
    }
}