<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeFamilyModelInterface;

class DatatypeFamily extends BaseModel implements DatatypeFamilyModelInterface
{
    protected $table='datatype_families';
}