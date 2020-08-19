<?php

namespace Zijinghua\Zvoyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Zijinghua\Zbasement\Events\Api\InterfaceAfterEvent;
use Zijinghua\Zbasement\Events\Api\InterfaceBeforeEvent;
use Zijinghua\Zvoyager\Http\Requests\UpdatePasswordRequest;
use Zijinghua\Zbasement\Http\Controllers\BaseController as BaseController;
use Zijinghua\Zbasement\Http\Requests\IndexRequest;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;
use Zijinghua\Zbasement\Http\Requests\StoreRequest;

class UserController extends BaseController
{
    public function updatePassword(UpdatePasswordRequest $request){
        $this->authorize('user',[]);
        $response=$this->execute($request,'updatePassword');
        return $response;
    }
    //不允许第三方账号直接通过路由创建用户，只能先去登录，登陆失败才能由登录程序创建用户
    public function store(StoreRequest $request){
        $response=$this->execute($request,'store');
        return $response;
    }

    public function show(ShowRequest $request){
//        $response=$this->execute($request,'show');
//        return $response;
    }

    public function index(IndexRequest $request){
//        Log::info(123);

        $response=$this->execute($request,'index');
        return $response;
    }
//    public function index(IndexRequest $request){
//        //发送事件
//        event(new InterfaceBeforeEvent($request));
//        //从request里获取参数（slug，查询参数）----记得在service里面过滤参数，去掉不用的参数
//        //找到对应的resource类
//        if(!isset($this->slug)){
//            $this->slug=getSlug($request);
//        }
//
//        $data=$request->all();
//        $service=$this->service($this->slug);
//        $message= $service->index($data);
//        $message->data->setPath('http://zvoyager.test/api/v1/user/index/');
//        $response=$message->response();
//        event(new InterfaceAfterEvent($request,$response));
//        return $response;
//    }
}
