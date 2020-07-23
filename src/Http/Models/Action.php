<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\ActionModelInterface;

class Action extends BaseModel implements ActionModelInterface
{
    protected $table='actions';
}