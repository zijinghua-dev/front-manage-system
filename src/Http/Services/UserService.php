<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zvoyager\Http\Contracts\UserServiceInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Traits\Credential;

class UserService extends BaseService implements UserServiceInterface
{
    use Credential;
    protected $username;
    //保存用户数据时，如果是带密码的，要先加密，然后再传给用户中心
    public function store($data){
        //所有第三方账号，如wechat_id，都不能直接创建用户，都是通过登录转过来注册的
        //所以，第三方账号，如wechat_id，都已经解密
        //第三方账号注册时，不能注册密码

        $credential=$this->getCredentials($data);
        if(isset($credential['password'])){
            $password['password']=Hash::make($credential['password']);
            unset($credential['password']);
        }
        $name=$credential;
        if(in_array(array_key_first($name),getConfigValue('zbasement.fields.auth.internal'))){
            $parameters=array_merge($name,$password);
            return parent::store($parameters);
        }elseif(in_array(array_key_first($name),getConfigValue('zbasement.fields.auth.external'))){
            return parent::store($name);
        }
    }
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
        $user=$repository->show(['uuid'=>$data['uuid']]);
        //检查旧密码是否一致
        if(Hash::check($data['pre_password'],$user->password)){
            //更改密码
            $password=Hash::make($data['password']);
            $user=$repository->update(['uuid'=>$data['uuid'],'password'=>$password]);
            $resource=$this->getResource($this->getSlug(),'updatePassword');
            $res = $this->messageResponse($this->getSlug(), 'UPDATEPASSWORD_SUCCESS', $user,$resource);
        }else{
            $res = $this->messageResponse($this->getSlug(), 'UPDATEPASSWORD_FAILED');
        }
        return $res;
    }

    public function groupIds($userId){
        $repository=Zsystem::repository('dataType');
        $dataTypeId=$repository->key('user');//slugId就是datatypeId
        $repository=$this->repository('groupObject');
        $parameter['search'][]=['field'=>'object_id','value'=>$userId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'datatype_id','value'=>$dataTypeId,'filter'=>'=','algorithm'=>'and'];
        $groups=$repository->index($parameter);
        if(isset($groups)&&($groups->count())){
            $groups=$groups->pluck('group_id')->toArray();
            $result=array_unique($groups);
            return $result;
        }


    }

    public function transferKey($request){
        $uuid=$request['uuid'];
        $slug=$request['slug'];
        $action=$request['action'];
        $repository=Zsystem::repository('user');
        $data[]=$repository->transferKey($uuid);
        if(emptyObjectOrArray($data)){
            $messageResponse=$this->messageResponse($slug,$action.'.validation.failed');
            return $messageResponse;
        }
        $messageResponse=$this->messageResponse($slug,$action.'.validation.success',$data);
        return $messageResponse;
    }
}