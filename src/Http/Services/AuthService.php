<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Zijinghua\Zvoyager\Http\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Traits\Credential;


class AuthService extends BaseService implements AuthServiceInterface
{
    use AuthenticatesUsers,Credential;

    protected $username;
    public function username()
    {
        return $this->username;
    }
    public function setUsername($data)
    {
        $internal=getConfigValue('zbasement.fields.auth.internal');
        $external=getConfigValue('zbasement.fields.auth.external');
        $allNames=array_merge($internal,$external,['account']);
        foreach ($data as $key=>$value){
            if(in_array($key,$allNames)){
                $this->username= $key;
                return;
            }
        }
    }
    public function login($request){
        //如果是第三方登录，不要验证
        $data=$request->all();
        $this->setUsername($data);

        if ($this->hasTooManyLoginAttempts($request)) {
            $code='zbasement.code.'.$this->getSlug().'.login.toomanyattempts';
//            $resource=$this->getResource($this->getSlug(),'login');
            $messageResponse=$this->messageResponse($code);
            return $messageResponse;
        }

        $credentials = $this->getCredentials($data);
        if(!isset($credentials)){
            //这里要抛出异常
            return;
        }
        if(in_array($this->username(),getConfigValue('zbasement.fields.auth.external'))){
            $loginResult=Auth::guard('api')->attemptExternal($credentials);
        }else{
            $loginResult=Auth::guard('api')->attempt($credentials);
        }
        if (isset($loginResult)&&(!empty($loginResult))) {
//            $code='zbasement.code.'.$this->getSlug().'.login.success';
            $resource=$this->getResource('user');
            $messageResponse=$this->messageResponse($this->getSlug(),'login.success',$loginResult,$resource);
            return $messageResponse;
        }

// If the login attempt was unsuccessful we will increment the number of attempts
// to login and redirect the user back to the login form. Of course, when this
// user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

//        $code='zbasement.code.'.$this->getSlug().'.login.error';
        $messageResponse=$this->messageResponse($this->getSlug(),'login.failed');
        return $messageResponse;
    }




}