<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zbasement\Facades\Zsystem;


class PermissionService  extends BaseGroupService implements PermissionServiceInterface
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

//    public function store(){
//        //创建权限
//        //如果带groupId，这个角色只能这个组用
//    }

//    public function update(){
//        //针对某个用户，更新某个组里的某个权限
//    }
////
////    public function delete(){
////        //删除角色
////    }
//
//    public function add(){
//        //添加某个权限到组里
//    }
//
//    public function clear(){
//        //针对某个用户，把组里的某个权限移除
//    }

    public function authorize($parameters){
        //给用户添加一对一权限

    }

    public function deauthorize(){
        //把用户的权限移除
    }

}