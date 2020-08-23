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
            foreach ($data['search'] as $key=>$item){
                if($data['search'][$key]['field']=='uuid'){
                    $data['search'][$key]['field']='id';
                    $data['search'][$key]['value']=$id;
                }
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
        if(isset($request->id)){
            $data['id']=$request->id;
        }

        if(!isset($data['slug'])){
            $data['slug']=getSlug($request);
        }
        if(!isset($data['datatypeId'])){
            $repository=Zsystem::repository('datatype');
            $data['datatypeId']=$repository->key($data['slug']);//slugId就是datatypeId
        }
        if(!isset($data['action'])){
//            $method=$request->route()->getActionName();
            list($class, $method) = explode('@', $request->route()->getActionName());
            $data['action']=$method;
        }
        //如果是三元操作符，把其余参数也进行转换
        if(!isset($data['actionId'])){
            $repository=Zsystem::repository('action');
            $search['search'][]=['field'=>'name','value'=>$data['action'],'filter'=>'=','algorithm'=>'or'];
            $search['search'][]=['field'=>'alias','value'=>$data['action'],'filter'=>'=','algorithm'=>'or'];
            $action=$repository->fetch($search);
            if($action->ternary){
                $data['ternaryActionId']=$action->ternary_id;
            }
            $data['actionId']=$action->id;
        }

        //然后拿到组ID
        //如果组ID为空，意味着用户要对自己创建的对象进行操作
        //当前组,前端传入的是uuid
        if(!isset($data['groupId'])){
            $data['groupId']=null;
        }
//        $groupId=$request->input('groupId');
//        if(isset($groupId)){
//            $data['groupId']=$groupId;
//        }

        //输入destinationGroupId,destinationUserUuid,destinationUserUuid需要转换成id
        //输入$actionId要转换为
//        $ternaryOperation=$this->ternaryOperation($data['actionId']);
//        if($ternaryOperation){
//            $data['destinationActionId']=$ternaryOperation;
//        }
//        if(isset($data['destinationUserId'])){
//
//                $repository=Zsystem::repository('user');
//                $result=$repository->transferKey(['uuid'=>$data['destinationUserUuid']]);
//            $data['destinationUserId']=$result;
//        }

        return $data;
    }

    public function ternaryOperation($actionId){
        //转换三元操作的类型
        $repository=Zsystem::repository('action');
        $search['search'][]=['field'=>'id','value'=>$actionId,'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'ternary','value'=>1,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(!isset($result)){
            return false;
        }
        return $result->ternary_id;
    }

    public function destinationAction(){

    }

    public function destinationGroup(){

    }

    public function destinationUser(){

    }
}