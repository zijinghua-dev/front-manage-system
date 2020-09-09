<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\PermissionModelInterface;

class Permission extends BaseModel implements PermissionModelInterface
{
    protected $table='permissions';
    protected $fillable=['key','action_id','datatype_id'];
}