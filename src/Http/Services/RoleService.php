<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\RoleServiceInterface;

class RoleService extends BaseGroupService implements RoleServiceInterface
{
    public function store($parameters){
        //创建角色
        //如果在公共组创建角色，则为template角色，
        //如果在admin组创建角色，则为默认角色
        if(isset($parameters['displayName'])){
            $data['display_name']=$parameters['displayName'];
        }
        $data['name']=$parameters['name'];
        $data['owner_group_id']=$parameters['groupId'];
        $data['owner_id']=$parameters['userId'];
        $data['datatype_id']=$parameters['datatypeId'];
        DB::beginTransaction();
        try {
            $result = parent::store($data);
            $authorizeService = Zsystem::service('authorize');
            $adminGroup = $authorizeService->getPlatformAdminGroup();
            if ($adminGroup->id == $data['owner_group_id']) {
                //如果在admin组创建角色，则为默认角色
                //默认角色还要更改角色的default字段
                $result->default = 1;
                $repository = $this->repository();
                $repository->update(['id' => $result->id, 'default' => 1]);
            } else {
                $publicGroup = $authorizeService->getPublicGroup();
                if ($publicGroup->id == $data['owner_group_id']) {
                    //如果在admin组创建角色，则为默认角色
                    //默认角色还要更改角色的default字段
                    $result->template = 1;
                    $repository = $this->repository();
                    $repository->update(['id' => $result->id, 'template' => 1]);
                }
            }

          DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }

    }

    public function update($parameters){
        //更新角色
        if(isset($parameters['displayName'])){
            $data['display_name']=$parameters['displayName'];
        }
        $data['id']=$parameters['id'];
        $data['name']=$parameters['name'];
        return parent::update($data);
    }

    public function delete($parameters){
        //删除角色，需要没有用户使用这个角色
        $repository=$this->repository('groupUserRole');
        $search['search'][]=['field'=>'role_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'or'];
        $dataSet=$repository->index($search);
        if($dataSet->count()>0){
            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.failed');
            return $messageResponse;
        }
        DB::beginTransaction();
        try {
            $repository=$this->repository('role');
            $repository->delete(['id'=>$parameters['id']]);
            $repository=$this->repository('groupRolePermission');
            $repository->delete(['role_id'=>$parameters['id']]);
            $repository=$this->repository('groupObject');
            $repository->delete(['id'=>$parameters['id'],'datatype_id'=>$parameters['datatypeId']]);
            DB::commit();
            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.success');
            return $messageResponse;
        } catch (Exception $e) {
            DB::rollback();
            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.failed');
            return $messageResponse;
        }
    }

//    public function add($parameters){
//        //角色添加到组里 groupObjects，组可以用哪些角色
//        //这个接口由组owner调用，避免组内角色太多
//        //role_id ,group_id ,
//    }
//
//    public function clear(){
//        //把组里的角色移除
//    }

//给角色在指定的组内添加权限；默认角色不能配置，因为它们不在任何组内；模板角色可配置，其存放于公共组内
//需要先将角色和权限从公共组转到该组，然后才能配置。为了方便用户，也可由前端一次性调用两个接口来完成这个任务
    //输入参数：group_id,role_id,permission_id;action_id,datatype_id可选
    //其实是四元操作符：用户，角色，权限（permission，permission又是datatype和action）
    public function assign($parameters){

        if(isset($parameters['authorizeActionId'])&&isset($parameters['authorizeDatatypeId'])){
            $repository=$this->repository('groupRolePermission');
            $repository->save(['role_id'=>$parameters['assignId'],'action_id'=>$parameters['assignActionId'],
                'datatype_id'=>$parameters['assignDatatypeId'],'group_id'=>$parameters['groupId']]);
            $messageResponse=$this->messageResponse($this->getSlug(),'assign.submit.success');
            return $messageResponse;
        }elseif(isset($parameters['assignUserId'])){
            $repository=$this->repository('datatype');
            $datatypeId=$repository->key('user');
            if(!isset($datatypeId)){
                $messageResponse=$this->messageResponse($this->getSlug(),'assign.submit.failed');
                return $messageResponse;
            }
            DB::beginTransaction();
            try {
            //先将用户添加进组

            $repository=$this->repository('groupObject');
            $repository->save(['datatype_id'=>$datatypeId,'object_id'=>$parameters['assignUserId'],
                'group_id'=>$parameters['groupId']]);
            //给用户授权
            $repository=$this->repository('groupUserRole');
            $repository->save(['role_id'=>$parameters['id'],'user_id'=>$parameters['assignUserId'],
                'group_id'=>$parameters['groupId']]);
                DB::commit();
                $messageResponse=$this->messageResponse($this->getSlug(),'assign.submit.success');
                return $messageResponse;
            } catch (Exception $e) {
                DB::rollback();
                $messageResponse=$this->messageResponse($this->getSlug(),'assign.submit.failed');
                return $messageResponse;
            }
        }
        $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.failed');
        return $messageResponse;
        //给用户添加角色
    }

    public function deauthorize($parameters){
        //把角色的权限移除
        //移除用户的角色
        if(isset($parameters['grpId'])){
            $repository=$this->repository('groupRolePermission');
            $repository->delete(['id'=>$parameters['grpId']]);
            $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.success');
            return $messageResponse;
        }elseif(isset($parameters['gurId'])){
            $repository=$this->repository('groupUserRole');
            $repository->delete(['id'=>$parameters['gurId']]);
            $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.success');
            return $messageResponse;
        }
        $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.failed');
        return $messageResponse;
    }

//    public function index($parameters)
//    {
//        //提取模板角色，合并到角色列表
//        return parent::index($parameters); // TODO: Change the autogenerated stub
//    }

//默认角色不分配到各组，系统自动将创始人配置owner角色，只有平台admin组可以index/编辑默认角色
//模板角色分配在公共组，所有用户都可以看到模板角色，但模板角色不能分享，只能复制到各组
//为用户赋予角色有两种分配形式：默认角色直接在groupUserRole里面分配，其他角色还要放置到groupObject中
//获取角色的权限在两个表：groupRolePermission和rolePermission
//role的relation主要是配置permission。
//如果有多个relation关系需要配置，比如，用户和组，用户和角色，用户和权限，有个方案：1，转化为容器，2，每个方法单独定义；
//3，全部用一个方法，用参数区分，公用一个权限
//角色配置页面入口，返回该组某角色下已经配置好的权限
    public function relation($parameters)
    {
        $groupId = $parameters['groupId'];
        if(isset($parameters['roleId'])){
            if(is_array($parameters['roleId'])){
                $roleIds=$parameters['roleId'];
            }else{
                $roleIds[]=$parameters['roleId'];
            }
        }else{
            //首先在groupObject把该组的自定义角色全部提取出来
            //各个组的默认角色不在groupObject里，直接分配到groupUserRole了，仅仅只有admin组才存有默认角色，到rolePermission中提取permission
            //如果不是默认角色，到groupRolePermission中提取
            $repository=$this->repository('datatype');
            $roleDatatypeId=$repository->key('role');
            $repository=$this->repository('groupObject');
            $result=$repository->index(['group_id'=>$groupId,'datatype_id'=>$roleDatatypeId]);
            if($result->count()==0){
                return $this->messageResponse($this->getSlug(),'show.submit.failed');
            }
            $roleIds=$result->pluck('object_id')->toArray();
        }

        $repository=$this->repository('role');
        $search['search'][]=['field'=>'id','value'=>$roleIds,'filter'=>'in','algorithm'=>'or'];
        $result=$repository->index($search);
        if($result->count()==0){
            return $this->messageResponse($this->getSlug(),'show.submit.failed');
        }
        $defaultRoleIds=$result->where('default',1)->pluck('id')->toArray();
        $templateRoleIds=$result->where('template',1)->pluck('id')->toArray();
        $dtRoleIds=array_merge($defaultRoleIds,$templateRoleIds);
        $customRoleIds=$result->where('default','!=',1)->Where('template','!=',1)->pluck('id')->toArray();

        $defaultSet=new Collection();
        $customSet=new Collection();
        $data['group_id']=$groupId;
        if($dtRoleIds){
            $data['role_id']=$dtRoleIds;
            $defaultSet=$repository->pivotFilter('permissionRole',$data);
        }
        if($customRoleIds){
            $data['role_id']=$customRoleIds;
            $customSet=$repository->pivotFilter('permission',$data);
        }

        if($defaultSet->count()){
            $customSet=$customSet->merge($defaultSet);
        }
        return $this->messageResponse($this->getSlug(),'show.submit.success',$customSet);
    }

public function relationupdate($parameters)
{

}
}