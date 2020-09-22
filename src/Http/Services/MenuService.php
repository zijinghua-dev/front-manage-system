<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\MenuServiceInterface;

class MenuService extends BaseGroupService implements MenuServiceInterface
{
    //请求参数：menuGroupId,objectGroupId，menuDatatypeId，menuActionId，
    //menuLever（返回菜单1，第一级；2，第二季；3，两级同传），menuType(点击类型：1，action；2，datatype）
    //当用户点击顶级菜单项，发送menuDatatypeId，menuType=2，menuGroupId可能null，其余为null；返回二级菜单的action列表，传入的menuGroupId原路传回。
    //点击二级菜单的action，发送menuActionId、menuGroupId，objectGroupId,menutype=1；返回二级菜单的action列表，如果有datatype，返回datatype列表
    //点击二级菜单的datatype，发送menuDatatypeId、objectGroupId和menuType=2;返回action列表
    public function index($parameters)
    {
        //personalGroupId，当前用户的个人组ID；当前选择的object对应的GroupId；isPersonalGroup，当前选择的object是不是个人组;
        //groupId，当前操作在那个组内
        //如果传递了MenuDatatype，没有MenuGroup，查看是否是group
        if(!isset( $parameters['personalGroupId'])){
            $parameters['personalGroupId']=$this->getPersonalGroupId($parameters['userId']);
        }
//        if(!isset( $parameters['objectGroupId'])){
//            $parameters['objectGroupId']=$this->getObjectGroupId($parameters);
//        }
        if(!isset( $parameters['menuGroupId'])&&isset($parameters['personalGroupId'])){
            $parameters['menuGroupId']=$parameters['personalGroupId'];
        }

        switch ($parameters['menuLever']) {
            case 1://只要顶级菜单
                $result= $this->topMenus($parameters);
                break;
            case 2://只要二级菜单
                $result= $this->secondMenus($parameters);
                break;
            default://一二级菜单同时回传
                $result= $this->allMenus($parameters);
        }
        $messageResponse=$this->messageResponse($this->getSlug(),'index.submit.success',$result);
        return $messageResponse;
    }

    protected function isPersonalGroup($groupId){
        $repository=$this->repository('group');
        $group=$repository->fetch(['id'=>$groupId]);
        if(!isset($group)){
            return false;
        }
        if(!isset($group->owner_id)||isset($group->owner_group_id)){
            return false;
        }
        return true;
    }

    //当用户不是平台owner和管理员，前端又没有传menuGroupId，需要将personalGroupId转为menuGroupId
    protected function getMenuGroupId($parameters){
        if(!isset($parameters['personalGroupId'])){
            if(!isset($parameters['menuObjectId'])){
                return null;
            }else{
                return $this->getObjectGroupId($parameters);
            }
        }else{
            if(!isset($parameters['menuObjectId'])){
                return $parameters['personalGroupId'];
            }else{
                return $this->getObjectGroupId($parameters);
            }
        }
    }

    protected function getObjectGroupId($parameters){
        if(isset($parameters['objectGroupId'])){
            return $parameters['objectGroupId'];
        }
        $datatypeId=$parameters['menuDatatypeId'];
        if(!isset($parameters['menuObjectId'])){
            return;
        }
        $objectId=$parameters['menuObjectId'];
        $repository=$this->repository('datatype');
        $datatype=$repository->fetch(['id'=>$datatypeId]);
        if(isset($datatype)){
            if(!isset($datatype->is_group)||(!$datatype->is_group)){
                return ;
            }
            $repository=$this->repository($datatype->slug);
            $datatype=$repository->fetch(['id'=>$objectId]);
            if(isset($datatype->group_id)){
                return $datatype->group_id;
            }
        }
    }

    protected function getPersonalGroupId($userId){
        $repository=$this->repository('group');
        $group=$repository->fetch(['owner_id'=>$userId,'owner_group_id'=>null]);
        if(isset($group)){
            return $group->id;
        }
    }

    protected function topMenus($parameters){
        //一级的groupId是personalGroupId,如果$parameters没有personalGroupId，重新获取一次，要求前端必传后可去掉代码
//        if(!isset($parameters['personalGroupId'])){
//            $parameters=$this->parameters($parameters);
//        }
//        $personGroupId=null;
        if(!isset($parameters['personalGroupId'])){
            $repository=$this->repository('datatype');
            $dataSet=$repository->index(['menu_level'=>1]);
            return ['datatype'=>$dataSet,'groupId'=>null];
        }
        //个人组内允许哪些数据类型，如果将来允许其他角色管理平台，要到groupDatatype获取数据类型
        $repository=$this->repository('datatype');
        $search['search'][]=['field'=>'slug','value'=>'group','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'groups','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'Group','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'Groups','filter'=>'=','algorithm'=>'or'];
        $groupType=$repository->index($search);
        return ['datatype'=>$groupType,'groupId'=>$parameters['personalGroupId']];
    }

    protected  function getActionsHasDatatype($id){
        $actions=['edit','show'];
        $repository=Zsystem::repository('action');
        $search['search'][]=['field'=>'name','value'=>$actions,'filter'=>'in','algorithm'=>'or'];
        $search['search'][]=['field'=>'alias','value'=>$actions,'filter'=>'in','algorithm'=>'or'];
        $actions=$repository->index($search);
        if($actions->count()>0){
            $result= $actions->where('id',$id);
            if($result->count()>0) {
                return true;
            }
        }
    }

    //点击二级菜单的action，发送menuActionId、menuGroupId，objectGroupId,menutype=1；返回二级菜单的action列表，如果有datatype，返回datatype列表
    //点击二级菜单的datatype，发送menuDatatypeId、objectGroupId和menuType=2;返回action列表
    protected function secondMenus($parameters){
        //点击action,必传menuActionId
        $datatypes=null;
        $actions=null;
        $menuGroupId=null;
        $objectGroupId=null;
        switch($parameters['menuType']){
            case 1://点击action
                if(isset($parameters['menuGroupId'])){
                    $menuGroupId =$parameters['menuGroupId'];
                }
                if(isset($parameters['objectGroupId'])){
                    $objectGroupId =$parameters['objectGroupId'];
                }
                $actions=$this->availableAction($parameters);
                if($this->getActionsHasDatatype($parameters['menuActionId'])){
                        $datatypes=$this->availableDatatype($parameters);
                }
                return ['datatype'=>$datatypes,'action'=>$actions,'menuGroupId'=>$menuGroupId,'objectGroupId'=>$objectGroupId];
            default://点击datatype
//                $menuGroupId=null;
                if(isset($parameters['objectGroupId'])){
                    //因为这里开始切换当前组了，所以要处理一下参数：如果点击了组列表，就会有objectGroupId，否则不用切换组
                    $menuGroupId=$parameters['objectGroupId'];
                    Arr::forget($parameters,['menuGroupId','objectGroupId']);
                    $parameters['menuGroupId']=$menuGroupId;
                }else{
                    //如果没有传objectGroupId，那就还用原来的group，不切换组
                    if(isset($parameters['menuGroupId'])){
                        $menuGroupId=$parameters['menuGroupId'];
                    }
                }
                $actions=$this->availableAction($parameters);
                return ['datatype'=>null,'action'=>$actions,'menuGroupId'=>$menuGroupId];
        }

//        if(isset($parameters['menuActionId'])){
//            if($this->getActionsHasDatatype($parameters['menuActionId'])){
//                if($parameters['menuType']==2){
//                    $datatypes=$this->availableDatatype($parameters);
//                    return ['datatype'=>$datatypes];
//                }
//            }
//        }else{
//            if($parameters['menuType']==1){
//                $actions=$this->availableAction($parameters);
//                return ['action'=>$actions];
//            }
//        }
//        $datatypes=[];
//        //由于前端点击菜单项，只能传递menuObject，和菜单项 menuDatatypeId， 并没有GroupID，所以，
//        //一级菜单的GroupID是personGroupID，如果是平台管理员，没有personGroupID，是全局；
//        //二级菜单：如果明确传递了objectGroupId，GroupID是objectGroupId，如果没有传递objectGroupId，就是menuDatatypeId对应的objectGroupId；
//        $datatypes=$this->availableDatatype($parameters);//没有指定当前组，是没有可包容的数据类型的
//        $actions=$this->availableAction($parameters);
//
//        return ['datatype'=>$datatypes,'action'=>$actions];
    }

    //如果没有传递menuDatatypeId，就默认topMenu选择第一个
    public function allMenus($parameters){
        $topMenus=$this->topMenus($parameters);
        if(!isset($parameters['menuDatatypeId'])){
            $datatype=$topMenus->datatype[0];
            $parameters['menuDatatypeId']=$datatype->id;
        }
        $secondMenus=$this->secondMenus($parameters);
        return ['topMenu'=>$topMenus,'secondMenu'=>$secondMenus];
    }

    //一个普通的容器数据对象，不是个人组，默认可以装载哪些对象
    public function normalDatatypes($parameters){
        //如果是个人组，退出
        if(!isset($parameters['objectGroupId'])){
            return new Collection();
        }
        if($this->isPersonalGroup($parameters['objectGroupId'])){
            return new Collection();
        }

        $repository = $this->repository('datatypeFamily');
        $dataSet = $repository->index(['datatype_id' => $parameters['menuDatatypeId']]);
        if($dataSet->count()==0){
            return new Collection();
        }
        $ids=$dataSet->pluck('child_datatype_id')->toArray();
        $repository = $this->repository('datatype');
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $dataSet = $repository->index($search);
        return $dataSet;
    }

    //每个单独的容器对象可以装载的其他数据对象，这个要一对一的配置
    public function singleDatatypes($groupId){
        //如果已经选择了一个组，有了objectID，就可以找到这个组默认的可容纳的数据类型，以及单独配置的数据类型限制
        $repository = $this->repository('groupDatatype');
        $dataSet = $repository->index(['group_id' => $groupId]);
        if($dataSet->count()==0){
            return new Collection();
        }
        $ids = $dataSet->pluck('datatype_id')->unique()->toArray();
        $repository = $this->repository('datatype');
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $dataSet = $repository->index($search);
        return $dataSet;
    }

    //个人组默认可以装载的其他数据对象
    public function personalDatatypes($parameters){
        //如果当前组不是个人组，返回空集
        if(!isset($parameters['objectGroupId'])){
            return new Collection();
        }
        if(!$this->isPersonalGroup($parameters['objectGroupId'])){
            return new Collection();
        }
        $repository=$this->repository('permission');
        $dataSet=$repository->index(['personal'=>1]);
        if($dataSet->count()==0){
            return new Collection();
        }
        $ids=$dataSet->pluck('datatype_id')->unique()->toArray();
        $repository = $this->repository('datatype');
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $dataSet = $repository->index($search);
        return $dataSet;
    }

    //一个用户在该组内授权可以index的数据对象类型
    public function authorizedDatatypes($parameters){
        $repository = $this->repository('action');
        $indexId = $repository->key('index');
        $service=Zsystem::service('authorize');
        $dataSet=$service->getParentPermissions($parameters['objectGroupId'],$parameters['userId'],null,$indexId);
        if($dataSet->count()==0){
            return $dataSet;
        }
        $ids=$dataSet->pluck('datatype_id')->unique()->toArray();
        if(emptyObjectOrArray($ids)){
            return new Collection();
        }
        $repository = $this->repository('datatype');
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $dataSet = $repository->index($search);
        return $dataSet;
    }

    //点击二级菜单的action，发送menuDatatypeId和groupId,menutype=2，menuActionId；index无需处理，show,edit要处理
    ////返回：如果当前groupId不为null，返回datatype列表，action列表不变；前端根据前端逻辑，决定datatype列表的显示或者隐藏（index不显示，show/edit显示）
    public function availableDatatype($parameters){
        //不是组，马上退出
        //如果选择了一个组，groupId不为null；如果是个人组，groupId也不为null
        if(!isset($parameters['objectGroupId'])){
            return new Collection();
        }
        //如果当前用户是平台owner和管理员，那么datatype就是组配置的全部对象
        //如果当前用户是普通用户，那么datatype就是组配置的全部对象，以及该用户的角色可index的对象类型的交集

        //先把全部对象类型取出来

            //如果是个人组，取个人组可容纳的对象类型
        $dataSet=$this->personalDatatypes($parameters);

        //不是个人组，取普通组可容纳的对象类型
        $normalDataSet=$this->normalDatatypes($parameters);
        //汇总一下,其实，这两个集合，必然一个为空
        $dataSet=$dataSet->merge($normalDataSet);

        //单独配置的类型
        $singleDataSet=$this->singleDatatypes($parameters['objectGroupId']);
        //汇总一下
        $dataSet=$dataSet->merge($singleDataSet);
        //如果是平台管理员，现在就可以返回了
        if(!isset($parameters['personalGroupId'])){
            return $dataSet;
        }
        //普通用户，如果进的不是自己的个人组，还要查看他的权限
        //如果是自己的个人组，现在可以返回了
        if($parameters['objectGroupId']==$parameters['personalGroupId']){
            return $dataSet;
        }

        //查看在该组可以index哪些类型
        $authorzieDataset=$this->authorizedDatatypes($parameters);
        if($authorzieDataset->count()==0){
            return $authorzieDataset;
        }
        //用户可以操作的，以及该组本身可以拥有的对象类型的差集~~~~~差集~~~~~~就是所求
        return $authorzieDataset->intersect($dataSet);
    }

    public function authorizeAction($parameters){
        $service=Zsystem::service('authorize');
        $dataSet=$service->getParentPermissions($parameters['menuGroupId'],$parameters['userId'],$parameters['menuDatatypeId'],null);
        if($dataSet->count()==0){
            return $dataSet;
        }
        //三元操作符的后一个操作，前一个操作有权限，执行后一个操作还需要另外判断权限
        //如果没有groupId，过滤掉依赖group的操作
        if(!isset($parameters['groupId'])){
            $dataSet=$dataSet->where('depend_group',null);
        }
        $ids=$dataSet->pluck('action_id')->unique()->toArray();
        if(emptyObjectOrArray($ids)){
            return new Collection();
        }
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $repository=$this->repository('action');
        return $repository->index($search);
    }

    //个人组默认的动作，要求当前用户是该组的所有人
    public function personalAction($parameters){
        if($parameters['menuGroupId']<>$parameters['personalGroupId']){
            return new Collection();
        }
        $repository=Zsystem::repository('permission');
        $dataSet=$repository->index(['personal'=>1,'datatype_id'=>$parameters['menuDatatypeId']]);
        if($dataSet->count()==0){
            return $dataSet;
        }
        $ids=$dataSet->pluck('action_id')->unique()->toArray();
        if(emptyObjectOrArray($ids)){
            return new Collection();
        }
        $search['search'][]=['field'=>'id','value'=>$ids,'filter'=>'in','algorithm'=>'or'];
        $repository=$this->repository('action');
        return $repository->index($search);
    }

    //输入参数：groupId,datatypeId,userId
    //输出参数：actionId集合
    //如果是个人组，默认有index权限
    //有一部分action依赖groupId，全局状态下没有操作
    public function availableAction($parameters){
        //平台管理员和owner对一个对象类型，可以操作所有的动作
        if(!isset($parameters['personalGroupId'])){
            return $this->allAction($parameters);
        }
        //普通用户要一对一检查是否给其授权
        $dataset= $this->authorizeAction($parameters);
        //如果这个操作对象是一个个人组，并且该用户就是个人组的所有者，那还有默认授权的动作
        $personalAction=$this->personalAction($parameters);
        if($personalAction->count()>0){
            $dataset= $dataset->merge($personalAction);
        }

        return $dataset;
    }

    //一个数据对象类型的全部可用动作，由permission来决定
    public function allAction($parameters){
        $datatypeId=$parameters['menuDatatypeId'];
        $repository=$this->repository('permission');
        $data['datatype_id']=$datatypeId;
        if(!isset($parameters['groupId'])){
            $data['depend_group']=1;
        }

        $dataSet=$repository->index(['datatype_id'=>$datatypeId]);
        if($dataSet->count()==0){
            return $dataSet;
        }
        $actionIds=$dataSet->pluck('action_id')->toArray();
        $repository=$this->repository('action');
        $search['search'][]=['field'=>'id','value'=>$actionIds,'filter'=>'in','algorithm'=>'or'];
        $result=$repository->index($search);
        return $result;
    }
}