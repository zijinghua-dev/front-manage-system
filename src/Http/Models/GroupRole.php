<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupRoleModelInterface;

class GroupRole extends BaseModel implements GroupRoleModelInterface
{
    protected $table='group_role_permissions';
}