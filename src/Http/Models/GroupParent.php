<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupParentModelInterface;

class GroupParent extends BaseModel implements GroupParentModelInterface
{
    protected $table='group_parents';
}