<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zbasement\Http\Models\BaseModel;
use Zijinghua\Zvoyager\Http\Contracts\GuopModelInterface;

class GroupUserObjectPermission extends BaseModel implements GuopModelInterface
{
    protected $table='group_user_object_permissions';
    protected $fillable=[
        'user_id',
        'datatype_id',
'object_id',
'action_id',
'group_id',
'schedule_begin',
'schedule_end',
    ];
}