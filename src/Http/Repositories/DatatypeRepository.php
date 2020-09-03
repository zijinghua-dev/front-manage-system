<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeRepositoryInterface;

class DatatypeRepository extends BaseRepository implements DatatypeRepositoryInterface
{
    public function key($slugName){
        //数据库里是复数形式，为什么啊！！！！！！！！
        //如果调用者不想处理参数转换，可以直接把slug传进来，这里进行单复数，大小写转换
        //如果传入了数组，表明参数已经转换完毕
        if(is_array($slugName)){
            $parameter=$slugName;
        }else{
            $pSlugName=Str::plural($slugName);
            $pSlugName=strtolower($pSlugName);
            $sSlugName=Str::singular($slugName);
            $sSlugName=strtolower($sSlugName);
            $parameter['search'][]=['field'=>'slug','value'=>$pSlugName,'filter'=>'=','algorithm'=>'or'];
            $parameter['search'][]=['field'=>'name','value'=>$pSlugName,'filter'=>'=','algorithm'=>'or'];
            $parameter['search'][]=['field'=>'slug','value'=>$sSlugName,'filter'=>'=','algorithm'=>'or'];
            $parameter['search'][]=['field'=>'name','value'=>$sSlugName,'filter'=>'=','algorithm'=>'or'];
        }

        return parent::key($parameter);
    }

    public function clear($parameters){
        //组内移除，并不删除
        //必须通过group_objects model来做
        //通过事务，删除关联记录
        $this->setSlug('groupDatatype');
        //组内移除需要将uuid变成 object_id，
        $groupId=$parameters['groupId'];
        $id=$parameters['id'];

        DB::beginTransaction();
        try {
            //删除group_object
            $model=Zsystem::model('groupObject');
            $where['group_id'] = $groupId;
            $where[] = [function($query) use ($id){
                $query->whereIn('datatype_id', $id);
            }];
            $model = $model::where($where);
            $this->softDelete($model);

            //删除group_object_permission
            $model=Zsystem::model('groupObjectPermission');
            unset($where);
            $where['group_id'] = $groupId;
            $where[] = [function($query) use ($id){
                $query->whereIn('datatype_id', $id);
            }];
            $model = $model::where($where);
            $this->softDelete($model);

            //删除group_role_permissions
            $model=Zsystem::model('groupRolePermission');
            unset($where);
            $where['group_id'] = $groupId;
            $where[] = [function($query) use ($id){
                $query->whereIn('datatype_id', $id);
            }];
            $model = $model::where($where);
            $this->softDelete($model);

            //删除group_user_permissions
            $model=Zsystem::model('groupUserPermission');
            unset($where);
            $where['group_id'] = $groupId;
            $where[] = [function($query) use ($id){
                $query->whereIn('datatype_id', $id);
            }];
            $model = $model::where($where);
            $this->softDelete($model);

            //最后删除group_datatypes
            $model=Zsystem::model($this->getSlug());
            $where['group_id'] = $groupId;
//        $ids = [1,2];
            $where[] = [function($query) use ($id){
                $query->whereIn('datatype_id', $id);
            }];
            $model = $model::where($where);
            $this->softDelete($model);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }
    }
}