<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\RoleRepositoryInterface;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function relation($parameters)
    {
        //roleId为空，获取该组全部角色，返回对应的权限
        $data['group_id']=$parameters['groupId'];

        if(isset($parameters['roleId'])){
            $data['role_id']=$parameters['roleId'];
        }
        //用户为角色自定义配置的权限
        $custom=parent::pivotFilter('permission',$data);
        $customSet=$custom->get();
        //系统配置默认角色的权限，如果是默认角色，查看对应权限；如果是
        $data=[];
        if(isset($parameters['roleId'])){
            $data['role_id']=$parameters['roleId'];
        }
        $default=parent::pivotFilter('permissionRole',$data);
        $defaultSet=$default->get();
        if($defaultSet->count()){
            $customSet->merge($defaultSet);
        }
        return $customSet;
    }
}