<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Support\Facades\DB;
use Zijinghua\Zvoyager\Http\Contracts\UserServiceInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Traits\Credential;

class UserService extends BaseGroupService implements UserServiceInterface
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
            $parameters['groupId']=null;
            $parameters['userId']=null;
            return parent::store($parameters);
        }elseif(in_array(array_key_first($name),getConfigValue('zbasement.fields.auth.external'))){
            $name['groupId']=null;
            $name['userId']=null;
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

    public function delete($parameters){
        $userId=$parameters['userId'];
        //删除用户
        DB::beginTransaction();
        try {
            //找出个人own的全部对象
            $repository=$this->repository('userObject');
            $search['search'][]=['field'=>'user_id','value'=>$userId,'filter'=>'=','algorithm'=>'and'];
            $dataSet=$repository->index($search);
            //先转换一下数据集结构
            $objects=[];
            foreach ($dataSet as $key=>$item){
                $objects[$item['datatype_id']][]=$item['object_id'];
            }
            $num=0;
            //删除数据对象本身
            foreach ($objects as $key=>$item){
                $repository=$this->repository('datatype');
                $datatype=$repository->show(['id'=>$key]);
                $slug=strtolower($datatype->slug);
                $repository=$this->repository($slug);
                $num=$repository->destroy(['id'=>$item]);
            }
            //删除拥有数据对象的记录
            $repository=$this->repository('userObject');
            $num=$num+$repository->destroy($search);
            //删除该用户的全部分享记录、一对一的全部权限记录
            $repository=Zsystem::repository('guop');
            $num=$num+$repository->destroy($search);
            //删除该用户的全部角色
            $repository=Zsystem::repository('groupUserRole');
            $num=$num+$repository->destroy($search);
            //删除该用户的全部角色对应的权限
            $repository=Zsystem::repository('groupUserPermission');
            $num=$num+$repository->destroy($search);

            DB::commit();

            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.success');
            return $messageResponse;
        } catch (Exception $e) {
            DB::rollback();
//            throw $e; //将exception继续抛出  生产环境可以修改为报错后的操作
            $messageResponse=$this->messageResponse($this->getSlug(),'delete.submit.failed');
            return $messageResponse;
        }
    }

//这是一个三元操作符，但在一个方法里完成：角色，用户，或者是角色，动作，对象类型
    //输入参数：group_id,id(用户)，role_id,authorize：0/1，删除/增加
    //调用本方法，需要角色在本组内，用户能操作本方法
    public function assign($parameters){
        //给用户添加/删除角色
        //添加角色，要求用户已经在组内
        $repository=$this->repository('groupUserRole');
        if(isset($parameters['assign'])&&$parameters['assign']){

            $result=$repository->save(['role_id'=>$parameters['roleId'],'user_id'=>$parameters['id'],'group_id'=>$parameters['groupId']]);

        }else{
            $result=$repository->delete(['role_id'=>$parameters['roleId'],'user_id'=>$parameters['id'],'group_id'=>$parameters['groupId']]);

            }
            $messageResponse=$this->messageResponse($this->getSlug(),'authorize.submit.success',$result);
            return $messageResponse;
    }

}