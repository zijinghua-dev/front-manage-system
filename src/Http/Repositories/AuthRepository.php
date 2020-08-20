<?php


namespace App\Http\Repositories;


use App\Http\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;

class AuthRepository extends BaseRepository implements AuthServiceInterface
{
    /**
     * 将客户端传入的account转换成config('auth.authFields')中的某一个字段
     * @param string $account
     * @return string
     * @throws \Exception
     */
    public function getAccountField(string $account)
    {
        $mayFields = getConfigValue('zbasement.fields.auth.internal');
        if (!is_array($mayFields) || empty($mayFields)) {
            throw new ConfigurationException('配置项zbasement.fields.auth.internal必须是数组, 且不能为空');
        }
        $user = $this->getUserUnion($mayFields, $account)->firstOrFail();

        foreach ($mayFields as $field) {
            if ($user->$field == $account) {
                return $field;
            }
        }

//        throw new \Exception('找不到account对应的列');
    }

    public function getUser($data){
        //输入参数是确定的email、mobile、username、wechatid当中的一个,只有两个元素
        //不接受accountid
        $model=Zsystem::model($this->getSlug());
        if(isset($data['password'])){
            unset($data['password']);
        }
        foreach ($data as $field=>$value){
            $model=$model->orWhere($field, $value);
        }
        $user=$model->first();
        return $user;
    }

    //需要覆盖基类的store方法，因为password保存的时候需要hash
    public function store($parameters){
        //这里要进行参数过滤
        $model=$this->model();
        //所有model都要实现fill方法，对输入参数进行过滤
        $parameters['password']=Hash::make($parameters['password']);
        $model->fill($parameters);
//        foreach ($parameters as $key => $value){
//            $model->$key=$value;
//        }
        $model->save();
        return $model;
    }
}