<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\MenuServiceInterface;

class MenuService extends BaseGroupService implements MenuServiceInterface
{
    //请求参数：menuDatatypeId，menuLever，menuObjectId（默认：personalGroupId，用户的个人组，不发送也可以后台解析）；
    //menuLever：1，第一级；2，第二季；3，两级同传；
    //当用户点击顶级菜单项，只有menuDatatypeId；如果不点击列表，仅仅只点击二级菜单项，也只有menuDatatypeId；view/edit/delete其实是在点击列表项后，才能点击的
    //点击列表中选项，就有 menuObjectId，和menuDatatypeId
    //返回数据后，前端要将datatypeId附着到二级菜单和列表项中，再次请求时，要将datatypeId传送过来
    public function index($parameters)
    {
        //personalGroupId，当前用户的个人组ID；当前选择的object对应的GroupId；isPersonalGroup，当前选择的object是不是个人组;
        //groupId，当前操作在那个组内
        //如果传递了MenuDatatype，没有MenuGroup，查看是否是group
        if(!isset( $parameters['personalGroupId'])){
            $parameters['personalGroupId']=$this->getPersonalGroupId($parameters['userId']);
        }
        if(!isset( $parameters['menuGroupId'])){
            $parameters['menuGroupId']=$this->getMenuGroupId($parameters);
        }
        if(!isset( $parameters['groupId'])){
            $parameters['groupId']=$this->getCurrentGroupId($parameters);
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

    protected function getCurrentGroupId($parameters){
        //当personalGroupId为null，当前menuObjectId为null，那么currentGroupId为null，现在是全局搜索
        //当personalGroupId为null，当前menuObjectId不为null，那么需要查看menuObjectId和menuDatatypeId对应的GroupId，
        //这就是currentGroupId，如果为null，现在是全局搜索
        //当personalGroupId不为null，当前menuObjectId为null，那么currentGroupId为personalGroupId
        //当personalGroupId不为null，当前menuObjectId也不为null，那么currentGroupId为menuObjectId和menuDatatypeId对应的GroupId
        if(!isset($parameters['personalGroupId'])){
            if(!isset($parameters['menuObjectId'])){
                return null;
            }else{
                return $this->getMenuGroupId($parameters);
            }
        }else{
            if(!isset($parameters['menuObjectId'])){
                return $parameters['personalGroupId'];
            }else{
                return $this->getMenuGroupId($parameters);
            }
        }
    }

    protected function getMenuGroupId($parameters){
        if(isset($parameters['menuGroupId'])){
            return $parameters['menuGroupId'];
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
        //如果personGroupId为null，代表是平台admin和平台owner
        if(!isset($parameters['personalGroupId'])){
            $repository=$this->repository('datatype');
            $dataSet=$repository->index(['menu_level'=>1]);
            return $dataSet;
        }
        //个人组内允许哪些数据类型，如果将来允许其他角色管理平台，要到groupDatatype获取数据类型
        $repository=$this->repository('datatype');
        $search['search'][]=['field'=>'slug','value'=>'group','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'groups','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'Group','filter'=>'=','algorithm'=>'or'];
        $search['search'][]=['field'=>'slug','value'=>'Groups','filter'=>'=','algorithm'=>'or'];
        $groupType=$repository->index($search);
        return $groupType;
    }

    //二级的group是用户选择的menuDatatypeId，如果menuDatatypeId是组，当前组即为menuDatatypeId对应的group
    //二级菜单输出的，是对当前组的动作列表，以及当前组包含的数据类型列表
    //必传 menuDatatypeId
    protected function secondMenus($parameters){
        $datatypes=[];
        //由于前端点击菜单项，只能传递menuGroupID，和菜单项 menuDatatypeId， 并没有GroupID，所以，
        //一级菜单的GroupID是personGroupID，如果是平台管理员，没有personGroupID，是全局；
        //二级菜单：如果明确传递了menuGroupId，GroupID是menuGroupId，如果没有传递menuGroupId，就是menuDatatypeId对应的menuGroupId；
        $datatypes=$this->availableDatatype($parameters);//没有指定当前组，是没有可包容的数据类型的
        $actions=$this->availableAction($parameters);

        return ['datatype'=>$datatypes,'action'=>$actions];
    }

    //如果没有传递menuDatatypeId，就默认topMenu选择第一个
    public function allMenus($parameters){
        $topMenus=$this->topMenus($parameters);
        if(!isset($parameters['menuDatatypeId'])){
            $datatype=$topMenus[0];
            $parameters['menuDatatypeId']=$datatype->id;
        }
        $secondMenus=$this->secondMenus($parameters);
        return ['topMenu'=>$topMenus,'secondMenu'=>$secondMenus];
    }

    //一个普通的容器数据对象，不是个人组，默认可以装载哪些对象
    public function normalDatatypes($parameters){
        //如果是个人组，退出
        if(!isset($parameters['groupId'])){
            return new Collection();
        }
        if($this->isPersonalGroup($parameters['groupId'])){
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
        if(!isset($parameters['groupId'])){
            return new Collection();
        }
        if(!$this->isPersonalGroup($parameters['groupId'])){
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
        $dataSet=$service->getParentPermissions($parameters['menuGroupId'],$parameters['userId'],null,$indexId);
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

    //输入参数：groupId,datatypeId,userId
    //输出参数：datatypeId集合
    public function availableDatatype($parameters){
        //不是组，马上退出
        //如果选择了一个组，groupId不为null；如果是个人组，groupId也不为null
        if(!isset($parameters['groupId'])){
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
        $singleDataSet=$this->singleDatatypes($parameters['groupId']);
        //汇总一下
        $dataSet=$dataSet->merge($singleDataSet);
        //如果是平台管理员，现在就可以返回了
        if(!isset($parameters['personalGroupId'])){
            return $dataSet;
        }
        //普通用户，如果进的不是自己的个人组，还要查看他的权限
        //如果是自己的个人组，现在可以返回了
        if($parameters['groupId']==$parameters['personalGroupId']){
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
        $dataSet=$service->getParentPermissions($parameters['groupId'],$parameters['userId'],$parameters['menuDatatypeId'],null);
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

    //个人组默认的动作，要求当前用户是该组的所有人
    public function personalAction($parameters){
        if($parameters['groupId']<>$parameters['personalGroupId']){
            return new Collection();
        }
        $repository=Zsystem::repository('permission');
        $dataSet=$repository->index(['personal'=>1]);
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