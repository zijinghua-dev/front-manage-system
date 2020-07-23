<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupPermissionModelInterface;

class GroupPermission extends BaseModel implements GroupPermissionModelInterface
{
    protected $table='group_user_permissions';
}