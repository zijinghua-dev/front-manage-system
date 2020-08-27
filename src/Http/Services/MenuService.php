<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\MenuServiceInterface;

class MenuService extends BaseGroupService implements MenuServiceInterface
{
    public function index($parameters)
    {
        //没有groupId和datatypeId时，返回topMenus
        //有groupId和datatypeId时，返回secondMenus

        if(!isset($parameters['groupId'])&&!isset($parameters['menuDatatypeId'])){
            $result= $this->topMenus($parameters['userId']);
        }else{
            if(isset($parameters['groupId'])){
                $data['groupId']=$parameters['groupId'];
            }
            if(isset($parameters['menuDatatypeId'])){
                $data['datatypeId']=$parameters['menuDatatypeId'];
            }

            $data['userId']=$parameters['userId'];
            $result= $this->secondMenus($data);
        }
        if(!isset($result)){
            return $this->messageResponse('menu', 'index.validation.failed');
        }
        return $this->messageResponse('menu', 'index.submit.success',$result);
    }

    protected function topMenus($userId){
        //平台admin，owner返回全部数据类型+“datatype=0”
        $service=Zsystem::service('authorize');
        if($service->isPlatformAdmin($userId)||($service->isPlatformOwner($userId))){
            $repository=$this->repository('datatype');
            $dataSet=$repository->index(null);
            return $dataSet;
        }

        $repository=$this->repository('datatype');
        $search['search'][]=['field'=>'slug','value'=>'group','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'groups','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'Group','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'Groups','filter'=>'=','algorithm'=>'or'];
        $groupType=$repository->fetch($search);
        return $groupType;
    }

    protected function secondMenus($parameters){
        //只有groupId时，为详情/修改/页面,返回当前组的动作列表和可承载的数据类型
        //有groupId和datatypeId的，为组内数据类型页面，返回组内数据类型的动作列表
        //只有datatypeId的，为数据类型页面，返回数据类型的动作列表
        //datatypeId=0的，为数据类型的详情/修改页面，把自己除开,返回增删改查动作
        if(isset($parameters['datatypeId'])){
            $service=Zsystem::service('datatype');
            $datatypes=$service->index($parameters);
        }

            $service=Zsystem::service('action');
            $actions=$service->index($parameters);
        if(isset($datatypes)){
            return ['datatype'=>$datatypes,'action'=>$actions];
        }
        return ['datatype'=>null,'action'=>$actions];
    }
}