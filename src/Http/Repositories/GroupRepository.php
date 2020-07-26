<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\GroupRepositoryInterface;

class GroupRepository extends BaseRepository implements GroupRepositoryInterface
{
    public function key($name){
        if(is_array($name)){
            $parameter=$name;
        }else{
            $parameter['search'][]=['field'=>'name','value'=>$name];
        }

        return parent::key($parameter);
    }

    //删除一个组，需要级联删除多个表
    //如果仅仅指示删除本表，则采用remove
    public function delete($parameters){
        $data['group_id']=$parameters['id'];
        DB::beginTransaction();
        try {
            $num=$this->remove(['id'=>$parameters['id']]);

            $repository=Zsystem::repository('groupDatatype');
            $num=$num+$repository->remove($data);
            ////删除group_object
            $repository=Zsystem::repository('groupObject');
            $num=$num+$repository->remove($data);
            //删除group_object_permission
            $repository=Zsystem::repository('groupObjectPermission');
            $num=$num+$repository->remove($data);
            //删除group_role_permissions
            $repository=Zsystem::repository('groupRolePermission');
            $num=$num+$repository->remove($data);
            //删除group_user_permissions
            $repository=Zsystem::repository('groupUserPermission');
            $num=$num+$repository->remove($data);

            DB::commit();
            return $num;
        } catch (Exception $e) {
            DB::rollback();
            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }

    }

    protected function deleteRelation($parameters){
        $model=Zsystem::model('groupDatatype');

    }
    public function destroy($parameters){
        //单一删除
        $parameters=$this->getIndexParameter($parameters);

        $model=$this->find($parameters);
        return $this->softDelete($model);

    }


    public function expand($parameters){
        $model=$this->model('groupDatatype');
        $data=[];
        if(is_array($parameters['datatypeId'])){
            foreach ($parameters['datatypeId'] as $key=>$datatypeId){
                $data[]=['group_id'=>$parameters['groupId'],'datatype_id'=>$datatypeId];
            }
        }else{
            $data[]=['group_id'=>$parameters['groupId'],'datatype_id'=>$parameters['datatypeId']];
        }
        try {
            return $model->insert($data);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1];
            if($errorCode == 1062){
                return;
            }else{
                throw $e;
            }
        }
        //这里会有数据库唯一索引冲突报错
    }

    //当删除一个组的属性时，各个关联表都要去除对应记录
    public function shrink($parameters){
        //groupId,datatypeId转为数据库字段名
        $data['group_id']=$parameters['groupId'];
        $data['datatype_id']=$parameters['datatypeId'];
        DB::beginTransaction();
        try {
            $repository=Zsystem::repository('groupDatatype');
            $num=$repository->remove($data);
            ////删除group_object
            $repository=Zsystem::repository('groupObject');
            $num=$num+$repository->remove($data);
            //删除group_object_permission
            $repository=Zsystem::repository('groupObjectPermission');
            $num=$num+$repository->remove($data);
            //删除group_role_permissions
            $repository=Zsystem::repository('groupRolePermission');
            $num=$num+$repository->remove($data);
            //删除group_user_permissions
            $repository=Zsystem::repository('groupUserPermission');
            $num=$num+$repository->remove($data);

            DB::commit();
            return $num;
        } catch (Exception $e) {
            DB::rollback();
            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }
//        $model=$this->model('groupDatatype');
//        $where['group_id'] = $parameters['groupId'];
//
//        if(is_array($parameters['datatypeId'])){
//            $id=$parameters['datatypeId'];
//            $where[] = [function($query) use ($id){
//                $query->whereIn('datatype_id', $id);
//            }];
//        }else{
//            $where['datatype_id']=$parameters['datatypeId'];
//        }
//        $model = $model::where($where);

//        try {
//
//            $repository=Zsystem::repository(groupDatatype);
//            $repository->remove($parameters);
//
//        } catch (QueryException $e) {
//            $errorCode = $e->errorInfo[1];
//            if($errorCode == 1062){
//                return;
//            }else{
//                throw $e;
//            }
//        }

        //这里会有数据库唯一索引冲突报错

    }
}