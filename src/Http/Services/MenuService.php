<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\MenuServiceInterface;

class MenuService extends BaseGroupService implements MenuServiceInterface
{
    //请求参数：menuObjectId，menuDatatypeId，menuLever：1，第一级；2，第二季；3，两级同传；默认：personalGroupId，用户的个人组，不发送也可以后台解析
    //menuObjectId，menuDatatypeId，都是用户选择的当前项，当用户点击列表，选择一项，即为menuObjectId，而menuDatatypeId是从菜单上获得的
    //当用户发送menuDatatypeId，意味着至少在topMenus上选择了一项，可以返回二级菜单了
    //如果用户发送menuObjectId，意味着至少在列表中选择了一项，可以返回该组内的datatype了
    public function index($parameters)
    {
        //如果传递了MenuDatatype，没有MenuGroup，查看是否是group
        if(!isset( $parameters['personalGroupId'])){
            $personalGroupId=$this->getPersonalGroupId($parameters['userId']);
            if(isset($personalGroupId)){
                $parameters['personalGroupId']=$personalGroupId;
            }
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

    protected function getMenuGroupId($datatypeId,$objectId){
        $repository=$this->repository('datatype');
        $datatype=$repository->fetch(['id'=>$datatypeId]);
        if(isset($datatype)){
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
        if(!isset($parameters['personalGroupId'])||$parameters['personalGroupId']==null){
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
        //由于前端点击菜单项，只能传递personGroupID，和菜单项 menuDatatypeId， 并没有GroupID，所以，
        //我们认为，menuDatatypeId为空的时候，是一级菜单，GroupID是personGroupID；
        //当menuDatatypeId不为空的时候，是二级菜单，GroupID是menuDatatypeId对应的menuGroupId；
        if(isset($parameters['menuObjectId'])&&isset($parameters['menuDatatypeId'])){
            $groupId=$this->getMenuGroupId($parameters['menuDatatypeId'],$parameters['menuObjectId']);
            if(isset($groupId)){
                $parameters['groupId']=$groupId;
                $datatypes=$this->availableDatatype($parameters);//没有指定当前组，是没有可包容的数据类型的
            }
        }

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

    //输入参数：groupId,datatypeId,userId
    //输出参数：datatypeId集合
    public function availableDatatype($parameters){

        //datatypeId如果是组，寻找这个组内可用的数据对象
        //如果是个人组，默认可以容纳部分类型
        $groupId=$parameters['groupId'];
        $userId=$parameters['userId'];
        //平台管理员owner可以操作组内所有类型

        $datatypeIds=[];
        $personalGroupId=null;
        if(!isset($parameters['personalGroupId'])) {
            //如果已经选择了一个组，有了组ID，先看这个组有没有单独的数据类型限制
            $repository = $this->repository('groupDatatype');
            $dataSet = $repository->index(['group_id' => $groupId]);
            if ($dataSet->count() > 0) {
                $datatypeIds = $dataSet->pluck('datatype_id')->unique()->toArray();
            }
        }else{
            //不是平台owner和管理员，要看他在该组的权限，能够index哪些datatype
//            $personalGroupId=$parameters['personalGroupId'];
            //首先找到index 的actionId
            $repository = $this->repository('action');
            $indexId = $repository->key('index');
            //查看在该组可以index哪些类型
            $service=Zsystem::service('authorize');
            $dataSet=$service->getParentPermissions($groupId,$userId,null,$indexId);
            if($dataSet->count()>0){
                $datatypeIds=$dataSet->pluck('datatype_id')->unique()->toArray();
            }
        }

        //如果是个人组，查看个人组默认的数据对象
        //什么是个人组:owner_id有，owner_group_id为null
        $repository=$this->repository('group');
        $dataSet=$repository->fetch(['id'=>$groupId]);
        if($dataSet->owner_id&&!isset($dataSet->owner_group_id)){
            $repository=$this->repository('permission');
            $dataSet=$repository->index(['personal'=>1]);
            if($dataSet->count()>0){
                $datatypeIds=array_merge($datatypeIds,$dataSet->pluck('datatype_id')->unique()->toArray());
            }
        }

        if(!empty($datatypeIds)){
            $search['search'][]=['field'=>'id','value'=>$datatypeIds,'filter'=>'in','algorithm'=>'or'];
        }
        if(!isset($search)){
            return new Collection();
        }
        $repository=$this->repository('datatype');
        $result=$repository->index($search);
        return $result;
    }

    //输入参数：groupId,datatypeId,userId
    //输出参数：actionId集合
    //如果是个人组，默认有index权限
    public function availableAction($parameters){
        //没有groupId的时候，是对全局进行操作，仅仅只有平台管理员和owner
        $userId=$parameters['userId'];
        $datatypeId=$parameters['menuDatatypeId'];
        $search=null;
        $groupId=null;
        if(isset($parameters['groupId']))
            {
                $groupId=$parameters['groupId'];
            }
        $personalGroupId=null;
        if(isset($parameters['personalGroupId'])){
            $personalGroupId=$parameters['personalGroupId'];
        }

        $actionIds=[];
        //如果是平台管理员owner，则直接从permission中获取该datatype的所有可用动作
        $repository=Zsystem::repository('permission');
        if(!isset($personalGroupId)){
            $dataSet=$repository->index(['datatype_id'=>$datatypeId]);
            if($dataSet->count()>0){
                $actionIds=$dataSet->pluck('action_id')->unique()->toArray();
            }
        }else{
            //如果不是个人组，或者是给个人组额外给了一些类型和动作，都是权限系统在处理
            $service=Zsystem::service('authorize');
            $dataSet=$service->getParentPermissions($groupId,$userId,$datatypeId,null);
            if($dataSet->count()>0){
                $actionIds=$dataSet->pluck('action_id')->unique()->toArray();
            }
//            else{
//                if(isset($groupId)&&($groupId!=$personalGroupId)){
//                    return new Collection();//如果没有任何可用数据类型，又不是个人组就要退出
//                }
//            }
            //两种情况是个人组：1，没有groupId;2,groupId就是$personalGroupId。如果是个人组，有默认的动作
            if(!isset($groupId)||($groupId==$personalGroupId)){
                $dataSet=$repository->index(['personal'=>1]);
                if($dataSet->count()>0){
                    $actionIds=array_merge($actionIds,$dataSet->pluck('action_id')->unique()->toArray());
                }
            }
        }

        if(isset($actionIds)){
            $search['search'][]=['field'=>'id','value'=>$actionIds,'filter'=>'in','algorithm'=>'or'];
        }
        if(!isset($search)){
            return new Collection();
        }
        $repository=$this->repository('action');
        $result=$repository->index($search);
        return $result;
    }

    //一个数据对象类型的全部可用动作，由permission来决定
    public function allAction($parameters){
        $datatypeId=$parameters['menuDatatypeId'];
        $repository=$this->repository('permission');
        $dataSet=$repository->getParentPermission(['datatype_id'=>$datatypeId]);
        if($dataSet->count()==0){
            return;
        }
        $actionIds=$dataSet->pluck('action_id')->toArray();
        $repository=$this->repository('action');
        $search['search'][]=['field'=>'id','value'=>$actionIds,'filter'=>'in','algorithm'=>'or'];
        $result=$repository->index($search);
        return $result;
    }
}