<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\ObjectActionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupUserPermissionModelInterface;

class ObjectAction extends BaseModel implements ObjectActionModelInterface
{
    protected $table='object_actions';
}