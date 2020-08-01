<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\ParameterServiceInterface;

class ParameterService extends BaseService implements ParameterServiceInterface
{
    public function hasId($request){
        $action=    $request['action'];
        $slug=$request['slug'];
        if(isset($request['uuid'])){
            $uuid=$request['uuid'];
            $data[]=['field'=>'uuid','value'=>$uuid];
            $messageResponse=$this->messageResponse($slug,$action.'.validation.success',$data );
            return $messageResponse;
        }else {
            if (!isset($request['id'])) {
                $messageResponse = $this->messageResponse($slug, $action . '.validation.failed');
                return $messageResponse;
            }
            $data[]=['field' => 'id', 'value' => $request['id']];
            $messageResponse = $this->messageResponse($slug, $action . '.validation.success', $data);
            return $messageResponse;
        }
    }

    public function replaceId($data,$id){
        //首先调整格式，如果只有一个元素，就不用数组了
        if(count($id)==1){
            $id=$id[0];
        }
        //如果$data是搜索格式
        if(isset($data['search'])){
            if($data['search']['field']=='uuid'){
                $data['search']['field']=='id';
                $data['search']['value']==$id;
            }
        }
        $data['id']=$id;
        return $data;
    }

    public function decryExternal($request){
        $data = $request->all();
        foreach ($data as $key=>$value) {
            if (in_array($key, getConfigValue('zbasement.fields.auth.external'))) {
                try {
//                    $key = encrypt( $data[$key] );
                    $data[$key] = decrypt($data[$key]);
                } catch (DecryptException $e) {
                    //这里应该再包装异常，放进response格式
                    throw new Exception('wechatid不正确');
                }
            }
        }
        return $data;
    }

    public function setAbility($request){
        $data = $request->all();
        if(!isset($data['slug'])){
            $data['slug']=getSlug($request);
        }
        if(!isset($data['datatypeId'])){
            $repository=Zsystem::repository('datatype');
            $data['datatypeId']=$repository->key($data['slug']);//slugId就是datatypeId
        }
        if(!isset($data['action'])){
            list($class, $method) = explode('@', $request->route()->getActionName());
            $data['action']=$method;
        }
        if(!isset($data['actionId'])){
            $repository=Zsystem::repository('action');
            $data['actionId']=$repository->key($data['action']);
        }

        //然后拿到组ID
        //如果组ID为空，意味着用户要对自己创建的对象进行操作
        //当前组,前端传入的是uuid
        if(!isset($data['groupId'])){
            $data['groupId']=null;
        }
        return $data;
    }
}