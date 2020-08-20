<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupUserPermissionModelInterface;

class GroupUserPermission extends BaseModel implements GroupUserPermissionModelInterface
{
    protected $table='group_user_permissions';
}