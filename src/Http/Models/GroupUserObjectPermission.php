<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GuopModelInterface;

class GroupUserObjectPermission extends BaseModel implements GuopModelInterface
{
    protected $table='group_user_object_permissions';
}