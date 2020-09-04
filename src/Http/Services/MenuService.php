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
//
        if(!isset($parameters['groupId'])&&!isset($parameters['menuDatatypeId'])){
            $result= $this->topMenus($parameters['userId']);
        }else{
//            if(isset($parameters['groupId'])){
//                $data['groupId']=$parameters['groupId'];
//            }
//            if(isset($parameters['menuDatatypeId'])){
//                $data['datatypeId']=$parameters['menuDatatypeId'];
//            }
//
//            $data['userId']=$parameters['userId'];
            $result= $this->secondMenus($parameters);
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
            $dataSet=$repository->index(['menu_level'=>1]);
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

    //二级菜单输出的，是对当前组的动作列表，以及当前组包含的数据类型列表
    protected function secondMenus($parameters){
        //只有groupId时，为详情/修改/页面,返回当前组的动作列表和可承载的数据类型
        //有groupId和datatypeId的，为组内数据类型页面，返回组内数据类型的动作列表
        //只有datatypeId的，为数据类型页面，返回数据类型的动作列表
        //datatypeId=0的，为数据类型的详情/修改页面，把自己除开,返回增删改查动作----这是例外吗？
        $datatypes=null;
        $actions=null;
        if(isset($parameters['groupId'])&&!isset($parameters['menuDatatypeId'])){
            //先提取动作列表
            $repository=Zsystem::repository('datatype');
            $datatypeId=$repository->key('group');
            if(!isset($datatypeId)){
                return;
            }
            $service=Zsystem::service('action');
            $result=$service->index(['datatypeId'=>$datatypeId,'objectId'=>$parameters['groupId'],
                'userId'=>$parameters['userId'],'groupId'=>$parameters['groupId']]);
            if(isset($result->data)){
                $actions=$result->data;
            }
            //再提取组内数据对象
            $service=Zsystem::service('datatype');
            $result=$service->index(['groupId'=>$parameters['groupId'],'userId'=>$parameters['userId']]);
            if(!emptyObjectOrArray($result->data)){
                $datatypes=$result->data;
            }
        }
        if(!isset($parameters['groupId'])&&isset($parameters['menuDatatypeId'])){
            $service=Zsystem::service('permission');

            $result=$service->index(['datatypeId'=>$parameters['menuDatatypeId'],'userId'=>$parameters['userId']]);
             if(!emptyObjectOrArray($result->data)){
                 $permissions=$result->data;
                 if($permissions->count()>0){
                     $actionIds=$permissions->pluck('action_id')->toArray();
                     $repository=Zsystem::repository('action');
                     $search['search'][]=['field'=>'id','value'=>$actionIds,'filter'=>'in','algorithm'=>'or'];
                     $actions=$repository->index($search);
                 }
             }
        }
        if(isset($parameters['groupId'])&&isset($parameters['menuDatatypeId'])){
            $service=Zsystem::service('action');
            $result=$service->index(['groupId'=>$parameters['groupId'],'userId'=>$parameters['userId'],'datatypeId'=>$parameters['menuDatatypeId']]);
            if(!emptyObjectOrArray($result->data)){
                $actions=$result->data;
            }
            $datatypes=$this->thirdMenus($parameters);
        }

        return ['datatype'=>$datatypes,'action'=>$actions];
    }

    //如果这个类型是一种组，那么，还可以有datatype子菜单
    //现在是特例，活动。当groupId，datatypeId同时存在，datatypeId是活动，要返回活动的动作和下一级：报名，卡，推广页面
    public function thirdMenus($parameters){
        $repository=Zsystem::repository('datatype');
        $datatypeId=$repository->key('campaign');
        if(isset($datatypeId)){
            if($datatypeId!=$parameters['menuDatatypeId']){
                return;
            }
            $search['search'][]=['field'=>'slug','value'=>'card','filter'=>'=','algorithm'=>'or'];
            $search['search'][]=['field'=>'slug','value'=>'url','filter'=>'=','algorithm'=>'or'];
            $result=$repository->index($search);
            if($result->count()==0){
                return;
            }
            return $result;
        }
    }

    public function availableDatatype(){

    }
    public function availableAction(){

    }
}