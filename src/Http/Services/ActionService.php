<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\ActionServiceInterface;

class ActionService extends BaseGroupService implements ActionServiceInterface
{
//输入参数：userId，groupId,datatypeId
    //用户可以看到/操作哪些数据类型
    public function index($parameters){
        //平台管理员/admin可以操作所有数据类型
        //普通用户要经过guop和gup查询其可操作的数据类型，根据groupId不同
        //获取用户的角色，查看是不是平台管理员/admin
        $service=Zsystem::service('authorize');
        $result=$service->isPlatformOwner($parameters['userId']);
        if(!isset($result)){
            $messageResponse=$this->messageResponse(null,'authorize.validation.failed');
            return $messageResponse;
        }
        if(!$result){
            $result=$service->isPlatformAdmin($parameters['userId']);
            if(!isset($result)){
                $messageResponse=$this->messageResponse(null,'authorize.validation.failed');
                return $messageResponse;
            }
        }
        if($result){
            $repository=$this->repository('permission');
            $result= $repository->index(['datatype_id'=>$parameters['datatypeId']]);
            $messageResponse=$this->messageResponse(null,'authorize.submit.success',$result);
            return $messageResponse;
        }
        //先拿到用户的角色
        $repository=$this->repository('groupUserRole');
        unset($search);
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $gur=$repository->index($search);
        if(isset($gur)){
            $result=$gur->orWhere('schedule_begin',null)->orWhere('schedule_begin'<now())->orWhere('schedule_end',null)->orWhere('schedule_end'>now());
        }
        if(isset($result)){
            $roleIds=$result->pluck('role_id')->toArray();
        }
            $repository=$this->repository('groupRolePermission');
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
            if(isset($parameters['datatypeId'])){
                $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            }
        if(isset($roleIds)){
            $search['search'][]=['field'=>'role_id','value'=>$roleIds,'filter'=>'in','algorithm'=>'and'];
        }
            $grp=$repository->index($search);
        if($grp->count()>0){
            $actionIds=$grp->pluck('action_id')->toArray();
        }
        unset($search);
        $search['search'][]=['field'=>'id','value'=>$actionIds,'filter'=>'in','algorithm'=>'or'];
        $repository=$this->repository('action');
        $result= $repository->index($search);
        $messageResponse=$this->messageResponse(null,'authorize.submit.success',$result);
        return $messageResponse;
    }
}