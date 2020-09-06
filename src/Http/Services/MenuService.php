<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\MenuServiceInterface;

class MenuService extends BaseGroupService implements MenuServiceInterface
{
    //请求参数：menugroupid，menudatatypeid，menulever：1，第一级；2，第二季；3，两级同传；usergroupid，用户的个人组
    //menugroupid，用户选择的当前组
    //usergroupid为空的时候，允许menugroupid为空，传递menudatatypeid，表明是平台管理员选择了第一极菜单
    //usergroupid不为空的时候，menugroupid为空，传递menudatatypeid，这个menudatatypeid只能是组，表明是普通用户选择了第一极菜单
    //当menugroupid不为空，menudatatypeid也不为空，选择了二级菜单，要返回三级菜单
    //当menudatatypeid不为空，要判断是否是组，如果是组，还要返回组内的对象类型
    public function index($parameters)
    {
        //如果传递了MenuDatatype，没有MenuGroup，查看是否是group
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

    protected function parameters($parameters){
        if(array_key_exists($parameters['menuGroupId'], $GLOBALS)){

        }
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
        if(!isset($parameters['personalGroupId'])){
            $personalGroupId=$this->
        }
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
        //只有groupId时，为详情/修改/页面,返回当前组的动作列表和可承载的数据类型
        //有groupId和datatypeId的，为组内数据类型页面，返回组内数据类型的动作列表
        //只有datatypeId的，为数据类型页面，返回数据类型的动作列表
        //datatypeId=0的，为数据类型的详情/修改页面，把自己除开,返回增删改查动作----这是例外吗？
        $datatypes=null;
        $actions=null;
        //再检查当前数据类型是不是groups类型
        //获取组可容纳的对象类型的列表
        if(isset($parameters['menuGroupId'])){
            $parameters['groupId']=$parameters['menuGroupId'];
            $datatypes=$this->availableDatatype($parameters);//没有指定当前组，是没有可包容的数据类型的
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
        $datatypeIds=[];
        $personalGroupId=$parameters['personalGroupId'];
        //先看这个组有没有单独的数据类型限制
        $repository=$this->repository('groupDatatype');
        $dataSet=$repository->index(['group_id'=>$groupId]);
        if($dataSet->count()>0){
            $datatypeIds=$dataSet->pluck('datatype_id')->toArray();
        }else{
            if($groupId!=$personalGroupId){
                return new Collection();//如果没有任何可用数据类型，又不是个人组就要退出
            }
        }
        $repository=$this->repository('datatype');
        if($groupId==$personalGroupId){
            //如果是个人组，查看个人组默认的数据对象
            $search['search'][]=['field'=>'personal','value'=>1,'filter'=>'=','algorithm'=>'or'];
        }
        $search['search'][]=['field'=>'id','value'=>$datatypeIds,'filter'=>'in','algorithm'=>'or'];
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

        $actionId=null;
        //如果是平台管理员owner，则直接从permission中获取该datatype的所有可用动作
        if(!isset($personalGroupId)){
            $service=Zsystem::repository('permission');
            $dataSet=$service->index(['datatype_id'=>$datatypeId]);
            if($dataSet->count()>0){
                $actionIds=$dataSet->pluck('action_id')->toArray();
            }
        }else{
            $service=Zsystem::service('authorize');
            $dataSet=$service->getParentPermissions($groupId,$userId,$datatypeId,$actionId);
            if($dataSet->count()>0){
                $actionIds=$dataSet->pluck('$action_id')->toArray();
            }else{
                if($groupId!=$personalGroupId){
                    return new Collection();//如果没有任何可用数据类型，又不是个人组就要退出
                }
            }
            //如果是个人组，有默认的动作
            if(isset($groupId)&&$groupId==$personalGroupId){
                $search['search'][]=['field'=>'personal','value'=>1,'filter'=>'=','algorithm'=>'or'];
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