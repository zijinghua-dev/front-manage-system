<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\OrganizeModelInterface;

class Organize extends BaseModel implements OrganizeModelInterface
{
    protected $table='organizes';
    protected $fillable=['name','describe','picture','group_id'];
}