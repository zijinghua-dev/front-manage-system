<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupRolePermissionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupUserPermissionModelInterface;

class GroupRolePermission extends BaseModel implements GroupRolePermissionModelInterface
{
    protected $table='group_role_permissions';
    protected $fillable=[
        'group_id','role_id','datatype_id','action_id',
    ];
}