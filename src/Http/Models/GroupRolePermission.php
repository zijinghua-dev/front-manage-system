<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupRolePermissionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupUserPermissionModelInterface;

class GroupRolePermission extends BaseModel implements GroupRolePermissionModelInterface
{
    protected $table='group_user_permissions';
}