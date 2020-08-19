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

    //这是一个三元操作符，但在一个方法里完成：角色，用户，或者是角色，动作，对象类型
    //authorize_user_id,id
    //调用本方法，需要角色在本组内，用户能操作本方法
    public function authorize($parameters){
        //给角色添加权限
        if(isset($parameters['authorizeActionId'])&&isset($parameters['authorizeDatatypeId'])){
            $repository=$this->repository('groupRolePermission');
            $repository->save(['role_id'=>$parameters['authorizeId'],'action_id'=>$parameters['authorizeActionId'],
                'datatype_id'=>$parameters['authorizeDatatypeId'],'group_id'=>$parameters['groupId']]);
            $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.success');
            return $messageResponse;
        }elseif(isset($parameters['authorizeUserId'])){
            $repository=$this->repository('datatype');
            $datatypeId=$repository->key('user');
            if(!isset($datatypeId)){
                $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.failed');
                return $messageResponse;
            }
            DB::beginTransaction();
            try {
            //先将用户添加进组

            $repository=$this->repository('groupObject');
            $repository->save(['datatype_id'=>$datatypeId,'user_id'=>$parameters['authorizeUserId'],
                'group_id'=>$parameters['groupId']]);
            //给用户授权
            $repository=$this->repository('groupUserRole');
            $repository->save(['role_id'=>$parameters['authorizeId'],'user_id'=>$parameters['authorizeUserId'],
                'group_id'=>$parameters['groupId']]);
                DB::commit();
                $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.success');
                return $messageResponse;
            } catch (Exception $e) {
                DB::rollback();
                $messageResponse=$this->messageResponse($this->getSlug(),'update.submit.failed');
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
}