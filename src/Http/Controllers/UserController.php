<?php

namespace Zijinghua\Zvoyager\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Zijinghua\Zbasement\Http\Controllers\BaseController as BaseController;
use Zijinghua\Zbasement\Http\Requests\IndexRequest;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;
use Zijinghua\Zbasement\Http\Requests\StoreRequest;

class UserController extends BaseController
{
    public function updatePassword(UpdatePasswordRequest $request){
        $response=$this->execute($request,'updatePassword');
        return $response;
    }
    //不允许第三方账号直接通过路由创建用户，只能先去登录，登陆失败才能由登录程序创建用户
    public function store(StoreRequest $request){
        $response=$this->execute($request,'store');
        return $response;
    }

    public function show(ShowRequest $request){
        $response=$this->execute($request,'show');
        return $response;
    }
    public function index(IndexRequest $request){
        $response=$this->execute($request,'index');
        return $response;
    }
}
