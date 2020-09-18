<?php

//默认的角色，配置了哪些权限
namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\PermissionRoleModelInterface;

class PermissionRole extends BaseModel implements PermissionRoleModelInterface{
    protected $table='permission_role';
}