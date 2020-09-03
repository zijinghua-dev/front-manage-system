<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupFamilyModelInterface;

class GroupFamily extends BaseModel implements GroupFamilyModelInterface
{
    protected $table='group_families';
    protected $fillable=['child_id','group_id'];
}