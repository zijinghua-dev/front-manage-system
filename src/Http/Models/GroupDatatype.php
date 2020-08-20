<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GroupDatatypeModelInterface;

class GroupDatatype extends BaseModel implements GroupDatatypeModelInterface
{
    protected $table='group_datatypes';
}