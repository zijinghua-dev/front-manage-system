<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Illuminate\Support\Facades\DB;
use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupModelInterface;

class Group extends BaseModel implements GroupModelInterface
{
    protected $table='groups';
    protected $fillable=['name','describe'];

    public function dataType(){
        return $this->hasMany('Zijinghua\Zvoyager\Http\Models\GroupDataType','group_id');
    }

    public function objectPermission(){
        return $this->hasMany('Zijinghua\Zvoyager\Http\Models\GroupObjectPermission','group_id');
    }

    public function object(){
        return $this->hasMany('Zijinghua\Zvoyager\Http\Models\GroupObject','group_id');
    }

    public function userPermission(){
        return $this->hasMany('Zijinghua\Zvoyager\Http\Models\GroupUserPermission','group_id');
    }

    public function rolePermission(){
        return $this->hasMany('Zijinghua\Zvoyager\Http\Models\GroupRolePermission','group_id');
    }

    public function deleteRelation(){
        DB::beginTransaction();
        try {
            //要同时删除group_datatype,group_objects,group_object_permissions,group_role,group_role_permissions,
            //group_user_permissions
            //
            $this->dataType()->softDelete();
            $this->object()->softDelete();
            $this->objectPermission()->softDelete();
            $this->userPermission()->softDelete();
            $this->rolePermission()->softDelete();
            $this->softDelete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
        }
    }
}