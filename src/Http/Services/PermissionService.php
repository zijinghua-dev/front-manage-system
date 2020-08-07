<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zbasement\Facades\Zsystem;

class PermissionService
{
    //内部调用，注意权限
    //参数：用户ID，对象类型ID，对象ID，组ID，动作ID，开始时间，结束时间
    public function createGroupPersonalPermission($parameters){
        $insert['group_id']=$parameters['groupId'];
        $insert['user_id']=$parameters['userId'];
        $insert['object_id']=$parameters['id'];
        $insert['datatype_id']=$parameters['datatypeId'];
        $insert['action_id']=$parameters['actionId'];
        if(isset($parameters['scheduleBegin'])){
            $insert['schedule_begin']=$parameters['scheduleBegin'];
        }
        if(isset($parameters['scheduleEnd'])){
            $insert['schedule_end']=$parameters['scheduleEnd'];
        }
        $repository=Zsystem::repository('guop');
        $result=$repository->store($insert);
        return $result;
    }
}