<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Http\Exceptions\HttpResponseException;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zbasement\Zsystem;

class AuthorizeService extends BaseService implements AuthorizeServiceInterface
{
    protected function parameter($userId,$groupId,$dataTypeId,$actionId){
        //输出一个数组，便于eloquent和cache/redis都能够使用
        //如果是redis，他会自动序列化，并不需要我们进行处理
    }
    public function checkPermission($userId,$groupId,$dataTypeId,$objectId,$actionId){
        //先看是否在一个组内
//        $this->setSlug('authorize');
        $groupService=Zsystem::service('group');
        $response=$groupService->inGroup($groupId,$userId,$dataTypeId,$objectId);
        if(!isset($response)||($response->status==false)){
            $messageResponse=$this->messageResponse($this->getSlug(),'authorize.failed');
            return $messageResponse;
        }
        //再看该用户的角色是否有对该类型对象的操作权限
        //如果用户id传错了，要报异常
        $parameter=$this->parameter($userId,$groupId,$dataTypeId,$actionId);
        $repository=Zsystem::repository('authorize');
        $result=$repository->fetch($parameter);
        if(!isset($result)||($result==false)){
            $messageResponse=$this->messageResponse($this->getSlug(),'authorize.failed');
        }else{
            $messageResponse=$this->messageResponse($this->getSlug(),'authorize.success');
        }

        return $messageResponse;

    }
}