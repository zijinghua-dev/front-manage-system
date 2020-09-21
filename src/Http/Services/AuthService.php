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
use Zijinghua\Zwechat\Client\Services\Wechat\SnsService;
use \Zijinghua\Zwechat\Client\Exceptions\Wechat\InvalidCodeException;
use Zijinghua\Zvoyager\Http\Services\GroupService;

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

    /**
     *当传入的参数中有oauth_code时，会根据code_type请求微信接口获取open id或包含union id的用户信息
     * code_type：取值范围：base,userinfo，默认base。会分别调用微信接口获取open id、包含union id的用户信息
     * 在Request中，增加wechat_id。将open id或union id作为wechat_id的值
     */
    public function setWechatInfo(Request $request)
    {
        $codeTyppe = $request->input('oauth_code_type', 'base');
        $map = ['base' => 'getOpenId', 'userinfo' => 'getUnionId'];
        if ($code = $request->input('oauth_code')) {
            $method = $map[$codeTyppe];
            try {
                $responseData = (new SnsService())->$method($code, config('wechat-client.wechat_app_id'));
            } catch (InvalidCodeException $exception) {
               return $this->messageResponse($this->getSlug(),'login.submit.failed');
            }

            $credential = Arr::only($responseData, ['unionid', 'open_id']);
            if (!$credential) {
                return $this->messageResponse($this->getSlug(),'login.submit.failed');
            }
            krsort($credential);
            $request->offsetSet('wechat_id', current($credential));
            if (@$responseData['headimgurl']) {
                $request->offsetSet('avatar', $responseData['headimgurl']);
            }
            $request->offsetUnset('oauth_code');
            $request->offsetUnset('oauth_code_type');
            $request->merge($responseData);
        }

        return true;
    }

    public function login($request){
        //如果是第三方登录，不要验证
        if (($res = $this->setWechatInfo($request))!==true) {
            return $res;
        }
        $data=$request->all();
        $this->setUsername($data);
        if ($this->hasTooManyLoginAttempts($request)) {
            $code='zbasement.code.'.$this->getSlug().'.login.toomanyattempts';
//            $resource=$this->getResource($this->getSlug(),'login');
            $messageResponse=$this->messageResponse($code);
            return $messageResponse;
        }

        $credentials = $this->getCredentials($data);
        if ($res===true) {
            $credentials = array_merge($credentials, $data);
        }

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
            $group = (new GroupService())->getUserGroup($loginResult['user']);
            $resource=$this->getResource('user');
            $messageResponse=$this->messageResponse(
                $this->getSlug(),
                'login.submit.success',
                $loginResult['user']->toArray(),
                $resource,
                ['token'=>$loginResult['token'], 'user_group' => $group->id]);
            return $messageResponse;
        }

// If the login attempt was unsuccessful we will increment the number of attempts
// to login and redirect the user back to the login form. Of course, when this
// user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

//        $code='zbasement.code.'.$this->getSlug().'.login.error';
        $messageResponse=$this->messageResponse($this->getSlug(),'login.submit.failed');
        return $messageResponse;
    }




}
