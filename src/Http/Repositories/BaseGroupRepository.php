<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Support\Facades\DB;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;

class BaseGroupRepository extends BaseRepository
{
    //如果是删除某个对象，也要同步移除groupobject,guop，objectaction
    //当前组内移除某个组对象;要同步移除groupobject,guop，objectaction
//    public function clear($parameters){
//        $data['group_id']=$parameters['groupId'];
//        $data['datatype_id']=$parameters['datatypeId'];
//        DB::beginTransaction();
//        try {
//            ////删除group_object
//            $repository=Zsystem::repository('groupObject');
//            $num=$repository->remove($data);
//            //删除group_user_object_permission
//            $repository=Zsystem::repository('guop');
//            $num=$num+$repository->remove($data);
//            //删除group_role_permissions
//            $repository=Zsystem::repository('objectAction');
//            $num=$num+$repository->remove($data);
//            //删除group_user_permissions
//
//            DB::commit();
//            return $num;
//        } catch (Exception $e) {
//            DB::rollback();
//            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
//        }

//    }
}