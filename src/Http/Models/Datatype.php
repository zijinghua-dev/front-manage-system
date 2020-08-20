<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeModelInterface;

class Datatype extends BaseModel implements DatatypeModelInterface
{
    protected $table='data_types';
}