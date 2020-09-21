<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Models\RestfulUser;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\GroupRepositoryInterface;
use Zijinghua\Zvoyager\Http\Models\Group;

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

    public function firstOrCreate(RestfulUser $user)
    {
        /* @var $model Group */
        $model=Zsystem::model($this->getSlug());
        $group = $model->firstOrCreate(['owner_id'=>$user->id]);
        Zsystem::repository('organize')->save(['group_id' => $group->id, 'name'=>'个人组']);
        return $group;
    }
}
