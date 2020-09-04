<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GurModelInterface;

class GroupUserRole extends BaseModel implements GurModelInterface
{
    protected $table='group_user_roles';
    protected $fillable=['group_id','user_id','role_id','schedule_begin','schedule_end','enabled'];
}