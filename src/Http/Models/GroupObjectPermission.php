<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupObjectPermissionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupUserPermissionModelInterface;

class GroupObjectPermission extends BaseModel implements GroupObjectPermissionModelInterface
{
    protected $table='group_user_permissions';
}