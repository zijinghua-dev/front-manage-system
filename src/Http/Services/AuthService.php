<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Zijinghua\Zvoyager\Http\Contracts\AuthServiceInterface;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Zijinghua\Zbasement\Http\Services\BaseService;


class AuthService extends BaseService implements AuthServiceInterface
{
    use AuthenticatesUsers;
    private $username;
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
        $data=$request->all();
        $this->setUsername($data);

        if ($this->hasTooManyLoginAttempts($request)) {
            $code='zbasement.code.'.$this->getSlug().'.login.toomanyattempts';
//            $resource=$this->getResource($this->getSlug(),'login');
            $messageResponse=$this->messageResponse($code);
            return $messageResponse;
        }

        $credentials = $this->getCredentials($data);

//        $guard=Auth::guard('api');
        if (Auth::guard('api')->attempt($credentials)) {
            $code='loginsuccess';
            return ;
        }

// If the login attempt was unsuccessful we will increment the number of attempts
// to login and redirect the user back to the login form. Of course, when this
// user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        $code='zbasement.code.'.$this->getSlug().'.login.failed';
        $messageResponse=$this->messageResponse($code);
        return $messageResponse;
    }

    protected function getCredentials($credentials): array
    {
        $filtedCredentials=[];
        foreach ($credentials as $field => $val) {
            if ($field == 'account') {
                $filtedCredentials=array_merge( $filtedCredentials, $this->getAccountField($val));
                $filtedCredentials['password']=$credentials['password'];
                break;
            }

            if (in_array($field, getConfigValue('zbasement.fields.auth.internal'))) {
                $this->username = $field;
                $filtedCredentials= Arr::only($credentials, [$field, 'password']);
                break;
            }

            if (in_array($field, getConfigValue('zbasement.fields.auth.external'))) {
                $this->username = $field;
                $filtedCredentials= Arr::only($credentials, [$field]);
                break;
            }
        }

        return $filtedCredentials;
    }
}