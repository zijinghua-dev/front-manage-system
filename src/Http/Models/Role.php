<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\RoleModelInterface;

class Role extends BaseModel implements RoleModelInterface
{
    protected $table='roles';
    protected $fillable=['name','display_name'];

    public function permission(){
        //首先返回
        $result=$this->belongsToMany('\Zijinghua\Zvoyager\Http\Models\Permission',
            'group_role_permissions',
            'role_id','permission_id');
        return $result;
    }

    public function permissionRole(){
        //首先返回
        $result=$this->belongsToMany('\Zijinghua\Zvoyager\Http\Models\Permission',
            'permission_role',
            'role_id','permission_id');
        return $result;
    }

    public function object(){
        //首先返回
        $result=$this->hasMany('\Zijinghua\Zvoyager\Http\Models\GroupObject');
        return $result;
    }
}