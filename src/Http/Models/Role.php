<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\RoleModelInterface;

class Role extends BaseModel implements RoleModelInterface
{
    protected $table='roles';
    protected $fillable=['name','display_name'];
}