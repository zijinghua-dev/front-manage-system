<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\RoleRepositoryInterface;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function setup($parameters)
    {
        //roleId为空，返回全部角色的权限
        $data['group_id']=$parameters['groupId'];

        if(isset($parameters['roleId'])){
            $data['role_id']=$parameters['roleId'];
            $model=$this->model('permission');
            $result=$model::whereHas('GroupRolePermission')->get();
            $result=$model::whereHas('GroupRolePermission', function ($query) use ($data){
                foreach ($data as $key=>$value){
                    $query->where($key, '=', $value);
                }
            });
            $result=$result->get();

        }
        $dataset=parent::pivotFilter('permission',$parameters);
        $dataset=$dataset->get();
        //检查是否是默认角色
        $default=parent::pivotFilter('permissionRole',$parameters);
        return $this->messageResponse($this->getSlug(),'show.submit.success',$dataset);
    }
}