<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\GroupRolePermissionServiceInterface;

class GroupRolePermissionService extends BaseService implements GroupRolePermissionServiceInterface
{
    public function show($parameters){
        //某个组中，全部角色的权限
        //在某个组中，某个角色的权限
        $repository=$this->repository('role');
        $dataset=$repository->pivotFilter('permission','group_id',$parameters['groupId']);
        //检查是否是默认角色
        $default=$repository->pivotFilter('permissionRole','group_id',$parameters['groupId']);
        return $this->messageResponse($this->getSlug(),'show.submit.success',$dataset);
//        $posts = App\Post::whereHas('groupRolePermission', function (Builder $query) use ($groupId) {
//            $query->where('groupId', '=', $groupId)->where('groupId', '=', $groupId);
//        })->get();


    }
}