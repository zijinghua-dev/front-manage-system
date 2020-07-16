<?php


namespace App\Http\Models;


use App\Http\Contracts\UserModelInterface;
use Zijinghua\Zbasement\Http\Models\BaseModel;

class User extends BaseModel implements UserModelInterface
{
    protected $table='users';
    protected $fillable = [
        'username', 'email', 'password', 'mobile', 'wechat_id',
    ];
}