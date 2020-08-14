<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
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


}