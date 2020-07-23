<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\DataTypeModelInterface;

class DataType extends BaseModel implements DataTypeModelInterface
{
    protected $table='data_types';
}