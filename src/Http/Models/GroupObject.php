<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupObjectModelInterface;

class GroupObject extends BaseModel implements GroupObjectModelInterface
{
    protected $table='group_objects';
}