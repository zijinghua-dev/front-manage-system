<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zvoyager\Http\Contracts\UserServiceInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;

class UserService extends BaseService implements UserServiceInterface
{
    //只返回第一个用户，并且返回这个用户的全部数据
//    public function fetch($data){
//        $repository=Zsystem::repository($this->getSlug());
//        $users=$repository->index($data);
//        if (isset($users)) {
//            $codeStr = 'ZBASEMENT_CODE_USER_FETCH_SUCCESS';
//            $res = $this->messageResponse($codeStr, $users[0]);
//        } else {
//            $codeStr = 'ZBASEMENT_CODE_USER_FETCH_FAILED';
//            $res = $this->messageResponse($codeStr);
//        }
//        return $res;
//    }
//    public function login($data){
//        $credentials = $this->getCredentials($data);
//        if (!isset($credentials)||(empty($credentials))) {
//            $codeStr = 'ZBASEMENT_CODE_USER_LOGIN_VALIDATION';
//            $res = $this->messageResponse($codeStr);
//            return $res;
//        }
//        if (isset($credentials['password']) && $credentials['password']) {
//            $data= $this->loginWithPassword($credentials);
//        } else {
//            $data=$this->userRepository->getUser($credentials);
//        }
//        if (isset($data)) {
//            $codeStr = 'ZBASEMENT_CODE_USER_LOGIN_SUCCESS';
//            $res = $this->messageResponse($codeStr, $data, '\App\Http\Resources\UserResource');
//        } else {
//            $codeStr = 'ZBASEMENT_CODE_USER_LOGIN_FAILED';
//            $res = $this->messageResponse($codeStr);
//        }
//        return $res;
//    }

//    protected function loginWithPassword(array $credentials)
//    {
//        /* @var $guard \Tymon\JWTAuth\JWTGuard */
//        $repository=Zsystem::repository($this->getSlug());
//        $password=$credentials['password'];
//        $user=$repository->getUser($credentials);
//        if(isset($user)){
//            if( Hash::check($password,$user->password)){
//                return $user;
//            }
//        }
//    }
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

    protected function getAccountField(string $value)
    {
        $result=[];
        $loginField = getConfigValue('zbasement.fields.auth.internal');
        foreach ($loginField as $key){
            $result[$key]=$value;
        }
        return $result;
    }

    public function updatePassword($data){
        $repository=$this->repository();
        //获取用户
        $user=$repository->show($data['uuid']);
        //检查旧密码是否一致
        if(Hash::check($data['pre_password'],$user->password)){
            //更改密码
            $password=Hash::make($data['password']);
            $repository->update(['uuid'=>$data['uuid'],'password'=>$password]);
            $codeStr = 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_SUCCESS';
            $res = $this->messageResponse($codeStr, $user, '\App\Http\Resources\UserResource');
        }else{
            $codeStr = 'ZBASEMENT_CODE_USER_UPDATEPASSWORD_ERROR';
            $res = $this->messageResponse($codeStr);
        }
        return $res;
    }
}