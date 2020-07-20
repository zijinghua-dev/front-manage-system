<?php

namespace Zijinghua\Zvoyager\Http\Controllers;

use Zijinghua\Zvoyager\Http\Requests\LoginRequest;
use Zijinghua\Zbasement\Events\Api\InterfaceAfterEvent;
use Zijinghua\Zbasement\Events\Api\InterfaceBeforeEvent;
use Zijinghua\Zbasement\Http\Controllers\BaseController as BaseController;

class AuthController extends BaseController
{
//    use AuthenticatesUsers;
//登录时可以是username，mobile，email和account，
//wechatID登录从另一个接口进入，直接调用authService，把三方ID写到request中
    public function login(LoginRequest $request){
        event(new InterfaceBeforeEvent($request));
        //从request里获取参数（slug，查询参数）----记得在service里面过滤参数，去掉不用的参数
        //找到对应的resource类
        if(!isset($this->slug)){
            $this->slug=getSlug($request);
        }

        $service=$this->service($this->slug);

        $message= $service->login($request);
        $response=$message->response();
        event(new InterfaceAfterEvent($request,$response));
        return $response;
    }
//    public function login(LoginRequest $request)
//    {
//        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
//        if ($this->hasTooManyLoginAttempts($request)) {
//            $this->fireLockoutEvent($request);
//
//            return $this->sendLockoutResponse($request);
//        }
//
//        $credentials = $this->credentials($request);
//
//        if ($this->guard()->attempt($credentials, $request->has('remember'))) {
//            return $this->sendLoginResponse($request);
//        }
//
//// If the login attempt was unsuccessful we will increment the number of attempts
//// to login and redirect the user back to the login form. Of course, when this
//// user surpasses their maximum number of attempts they will get locked out.
//        $this->incrementLoginAttempts($request);
//
//        return $this->sendFailedLoginResponse($request);
//    }
}
