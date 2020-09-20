<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Zijinghua\Zvoyager\Http\Contracts\RoleServiceInterface;

class RoleService extends BaseGroupService implements RoleServiceInterface
{
    public function store($parameters){
        //创建角色
        //如果带groupId，这个角色就只能这个组用了，目前用户自己暂时不能创建角色
        if(isset($parameters['displayName'])){
            $data['display_name']=$parameters['displayName'];
        }
        $data['name']=$parameters['name'];
        return parent::store($data);
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
//role的relation主要是配置permission。
//如果有多个relation关系需要配置，比如，用户和组，用户和角色，用户和权限，有个方案：1，转化为容器，2，每个方法单独定义；
//3，全部用一个方法，用参数区分，公用一个权限
//角色配置页面入口，返回该组某角色下已经配置好的权限
//输入参数：roleId为空，返回该组全部角色的全部;group_id必传，group为null则直接返回空列表
    public function relation($parameters)
    {
        $repository=$this->repository();
        //groupId不能为空，默认角色不在grouprolepermission表，而是rolepermission表里
        //roleId为空，获取该组全部角色，返回对应的权限
        //不能groupId和roleId为空同时出现
//        if(isset($parameters['group_id'])) {
            $data['group_id'] = $parameters['groupId'];
//        }
        if(isset($parameters['roleId'])){
            $data['role_id']=$parameters['roleId'];
        }
        //用户为角色自定义配置的权限
        $customSet=$repository->pivotFilter('permission',$data);

        //系统配置默认角色的权限，如果是默认角色，查看对应权限；如果是
        $data=[];
        if(isset($parameters['roleId'])){
            $data['role_id']=$parameters['roleId'];
        }
        $defaultSet=$repository->pivotFilter('permissionRole',$data);
        if($defaultSet->count()){
            $customSet->merge($defaultSet);
        }
        return $this->messageResponse($this->getSlug(),'show.submit.success',$customSet);
    }

public function relationupdate($parameters)
{

}
}