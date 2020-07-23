<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupDataTypeModelInterface;

class GroupDataType extends BaseModel implements GroupDataTypeModelInterface
{
    protected $table='group_datatypes';
}