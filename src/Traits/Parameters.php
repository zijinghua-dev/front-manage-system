<?php


namespace Zijinghua\Zvoyager\Traits;


use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Zijinghua\Zbasement\Facades\Zsystem;

trait Parameters
{
    //同时将uuid转为id
    //不必再做
    public function setObject($request){
        $data = $request->all();
        if(!isset($data['uuid'])){
            $data['uuid']=null;
            //看一下有没有放到json字符串里面
            if(isset($data['search'])){
                foreach ($data['search'] as $key=>$item){
                    if($item['field']=='uuid'){
                        $data['uuid'][]=$item['value'];
                        $request->replace($data);
                    }
                }
            }
        }
        return $request;
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
            $request->replace($data);
    return $request;
}
}