<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\AuthorizeServiceInterface;

class AuthorizeService extends BaseService implements AuthorizeServiceInterface
{
    protected function groupPermissionParameter($userId,$groupId){
        //输出一个数组，便于eloquent和cache/redis都能够使用
        //如果是redis，他会自动序列化，并不需要我们进行处理
    }

    //查找该组的父组，同时查看该用户是否在父组内
    //组的父组是不能有中断的

    protected function parentSearch($userId,$groupId){
        $parameter['search'][]=['field'=>'user_id','value'=>$userId,'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $repository=Zsystem::repository('groupDataType');
        $parent=$repository->fetch($parameter);
        if(isset($parent)){
            return $parent->id;
        }
    }

    protected function addPermission($parentPermissions,$permissions){
        foreach ($parentPermissions as $key=>$item){
            $result=$permissions->where('datatype_id',$item['datatype_id'])->where('action_id',$item['action_id']);
            if($result->count()==0){
                $permissions->push($item);
            }
        }
        return $permissions;
    }

    protected function permissions($parameters){
//获取该用户在该组的permissions
        $parameter['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $parameter['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $repository=Zsystem::repository('groupUserPermission');
        $permissions=$repository->index($parameter);
        return $permissions;
    }

    public function checkPlatformOwnerPermission($parameters){
        //只要是平台owner组成员，或者是平台owner组的owner，都可以授权
        //先找到平台owner组
        $group=$this->getPlatformOwnerGroup();
        if(!isset($group)){
            return ;//出错啦
        }

        //看看是不是平台owner组的owner
        if($group->owner_id==$parameters['userId']){
            return true;
        }
        //如果不是平台owner组的owner，看看是不是平台owner组的成员
        //这个地方datatypeId是user的ID，不是slug
        $repository=Zsystem::repository('datatype');
        $datatypeId=$repository->key('user');
        if(!isset($datatypeId)){
            return ;//出错啦
        }
        if($datatypeId==0){
            return ;//出错啦
        }
        $result=$this->inGroup(['datatypeId'=>$datatypeId,'datatypeSlug'=>'user',
            'id'=>$parameters['userId'],'groupId'=>$group->id]);
        return $result;
    }

    public function checkPlatformAdminPermission($parameters){
    //这个方法需要在checkOwnerPermission之后使用，否则，即便是平台owner，也有可能没有权限进入任何地方
        //先找到平台管理组
        $group=$this->getPlatformAdminGroup();
        if(!isset($group)){
            return ;//出错啦
        }
        //检查用户是否是平台管理组的owner
        if($group->owner_id==$parameters['userId']){
            return true;
        }
        //用户是否是平台管理组的成员
        $repository=Zsystem::repository('datatype');
        $datatypeId=$repository->key('user');
        if(!isset($datatypeId)){
            return ;//出错啦
        }
        if($datatypeId==0){
            return ;//出错啦
        }
        $result=$this->inGroup(['datatypeSlug'=>'user','id'=>$parameters['userId'],
            'datatypeId'=>$datatypeId,'groupId'=>$group->id]);
        if(!isset($result)){
            return false;//该用户不是admin组成员
        }
        if(!$result){
            return false;//该用户不是admin组成员
        }
        //现在排除操作对象是否是平台owner组的
        $ownerGroup=$this->getPlatformOwnerGroup();
        if(!isset($ownerGroup)){
            return ;//出错啦
        }
        $result=$this->inFamilyGroup(['id'=>$parameters['id'],'datatypeId'=>['datatypeId'],'groupId'=>$ownerGroup->id]);
        if($result){
            return false;
        }
        return true;
    }

    public function isPowerOwner($userId,$datatypeSlug,$objectId){
        //当前对象是不是仅仅被该用户拥有？并没有被任何一个组拥有
        //该用户是否通过子组拥有该对象
        //首先获取该对象，拿到owner和owner组

        if(($datatypeSlug=='user')or($datatypeSlug=='users')or($datatypeSlug=='User')or($datatypeSlug=='Users')){
            //用户类型是一个特殊数据对象，没有owner，只有owner_group
            if($userId==$objectId){
                return true;
            }
            return false;
        }
        $repository=Zsystem::repository($datatypeSlug);
        $search['search'][]=['field'=>'id','value'=>$objectId,"filter"=>"=",'algorithm'=>'and'];
        $object=$repository->fetch($search);
        if(!isset($object)){
            return;
        }
        if(isset($object->owner_group_id)&&($object->owner_group_id>0)){
            return false;//有了组owner，任何owner用户的权限都没有用了
        }

        if($object->owner_id==$userId){
            return true;
        }
        return false;
    }

    //用户无需存在于公共组；如果当前组是公共组，允许一切公共操作
    //公共组的公共操作无需objectID：如index之类
    //公共组的公共操作可以不管用户角色，
    public function checkPublicAction($parameters){
//        $datatypeId,$objectId,$actionId
        //这个对象在公共组里吗？动作也许可了吗？
        //首先找到公共组
        if(!isset($parameters['groupId'])){
            return false;
        }
        $group=$this->getPublicGroup();
        if(!isset($group)){
            return ;//出错啦
        }
        if($group->id!=$parameters['groupId']){
            return false;
        }
        if(isset($parameters['id'])){
            //检查一对一权限
            $repository=Zsystem::repository('guop');
            $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'user_id','value'=>null,'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'action_id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->fetch($search);
            if(isset($result)){
                return true;
            }
        }
       //检查角色权限
        $repository=Zsystem::repository('groupRolePermission');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(isset($result)){
            return true;
        }
        return false;
    }

    public function isTernaryAction($actionId=null,$actionName=null){
        $repository=Zsystem::repository('action');
        if(isset($actionId)){
            $search['search'][]=['field'=>'id','value'=>$actionId,'filter'=>'=','algorithm'=>'and'];
        }
        if(isset($actionName)){
            $search['search'][]=['field'=>'name','value'=>$actionName,'filter'=>'=','algorithm'=>'and'];
        }
        if(!isset($search)){
            return;
        }
        $search['search'][]=['field'=>'ternary','value'=>null,'filter'=>'<>','algorithm'=>'and'];
        $search['search'][]=['field'=>'ternary','value'=>0,'filter'=>'<>','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(isset($result)){
            return true;
        }
        return $result;
    }

    public function checkGroupPermission($parameters){
        //如果不是该用户单独拥有，也不是公共操作，检查有没有单独为该用户分配该对象的权限？group_user_object_permissions.
        $result=$this->checkGuop($parameters);
        if($result){
            return true;
        }
        //group_user_object_permissions表里没有数据，而groupId又为null，肯定该用户是没有权限的了
        if(!isset($parameters['groupId'])){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
            return false;//
        }

        //操作对象是不是就是当前组?还是当前组内的一个数据对象
        $result=$this->isCurrentGroup($parameters);
        if(!isset($result)){
            return $result;
        }
        if(!$result){
            //如果objectId存在，应该是show之类的动作，不存在，应该是index之类的动作
            //如果group_user_object_permissions表里没有数据，需要看一看该用户的组角色是不是拥有权限
            //如果groupId不为null，除了添加到组的动作，该对象都必须在该组内（子组内）。如果不在，要报参数错误，因为提交前需要确认在组内？
            //如果有操作对象，做两个检查，第一，在不在当前组，第二，在当前组里有没有操作属性
            //不管有没有操作对象，都要做一个检查，组里面能否容纳这个类型的对象
            if(isset($parameters['id'])){
                $result=$this->inGroup($parameters);
                if(!$result){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
                    return $result;//
                }
                //这个动作依赖对象的属性吗？从组内移除，不依赖对象，
                if($this->isObjectAbility($parameters)){
                    //在当前组，该对象有对应的动作的属性吗？没有，报权限错误
                    $result=$this->objectHasAbility($parameters);
                    if(!$result){
//                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
                        return $result;//
                    }
                }
            }
            //该组有容纳该类型对象的属性吗？没有，报权限错误
            $result=$this->groupHasDatatype($parameters);
            if(!$result){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
                return $result;//
            }
        }

        //找出用户的角色在当前组内拥有的最高权限
        $result=$this->checkGroupRoleObjectPermissions($parameters);

        if($result){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.success');
            return true;//
        }else{
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
            return $result;//
        }
    }

    //返回null，出错了，返回false，没有找到
    public function checkPersonalOwnerPermission($parameters){
        //如果没有id，证明不是一对一
        if(!isset($parameters['id'])){
            return false;
        }
        $result=$this->isPowerOwner($parameters['userId'],$parameters['slug'],$parameters['id']);
        if($result){
            //如果不在组里操作，也不是三元操作符，那就可以操作了
            if(!isset($parameters['groupId'])) {
                if(!$this->isTernaryAction($parameters['actionId'])){
                    return true;
                }else{
                    //如果是三元操作，检查目的组权限
                    $result=$this->checkGroupPermission(['groupId'=>$parameters['destinationGroupId'],
                        'userId'=>$parameters['userId'],'datatypeId'=>$parameters['datatypeId'],'slug'=>$parameters['slug'],
                        'actionId'=>$parameters['destinationActionId']]);
                    return $result;
                }
            }else{
                return false;//不允许在组里操作个人owner的对象，只能把个人的对象发布到组里（从组外到组内）
            }
        }
        return $result;
    }

    public function checkUserPermission($parameters){
        //非平台owner和管理员，没有group_id，只能查看个人own和分享的对象：mine，或者是一对一
        //非平台owner和管理员，有object_id，先检查是不是单独拥有的对象，单独拥有，可以操作一切非三元动作

            //该用户单独拥有该对象吗？
            $result = $this->checkPersonalOwnerPermission($parameters);
            if (!isset($result)) {
                return;//
            } elseif ($result) {
                return $result;
            }

            //是公共组的公开操作吗？
            $result=$this->checkPublicAction($parameters);
            if(!isset($result)){
                return ;
            }elseif($result){
                return $result;
            }
//        }

        //检查在源组的权限。允许用户不在该组内，但操作对象一定要在该组内
        $result=$this->checkGroupPermission($parameters);
        if(!isset($result)){
            return ;
        }elseif($result){
            //检查在目的组的权限
            if($this->isTernaryAction($parameters['actionId'])) {
                return $this->checkGroupPermission(['groupId' => $parameters['destinationGroupId'],
                    'userId' => $parameters['userId'], 'datatypeId' => $parameters['datatypeId'], 'slug' => $parameters['slug'],
                    'actionId' => $parameters['destinationActionId']]);
            }
            return true;
        }
        return false;
    }

    public function checkPermission($parameters){
        //首先检查是不是平台owner
        //然后检查是不是平台admin
        //最后检查普通用户权限
        //如果组ID为1，只检查该用户是否在该组内，不用检查对象是否在该组内
        //如果组ID为2，只检查该用户是否在该组内，以及操作对象是否是在1组内，只要不是1组成员，都可以操作
//        $userId,$groupId,$dataTypeId,$objectId,$actionId
        //更换
        try{
            $result=$this->checkPlatformOwnerPermission($parameters);
            if($result){
                $messageResponse=$this->messageResponse($parameters['slug'],'authorize.submit.success');
                return $messageResponse;
            }
            $result=$this->checkPlatformAdminPermission($parameters);
            if($result){
                $messageResponse=$this->messageResponse($parameters['slug'],'authorize.submit.success');
                return $messageResponse;
            }

            $result=$this->checkUserPermission($parameters);
            if($result){
                $messageResponse=$this->messageResponse($parameters['slug'],'authorize.submit.success');
                return $messageResponse;
            }
            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.submit.failed');
            return $messageResponse;
        }catch (\Exception $e){
            //只捕捉各种参数出错的异常
            //数据库模型丢失异常
        }

    }

    //当前用户是不是第一组成员？
    //当前操作对象是否在第一组？
    //当前操作对象是否是第一组成员拥有？
    //当前组或者是目的组，是不是第一组？
    //传入参数：slug(必传，调用的slug)，datatype_slug（必传，现在要检查的对象的datatype），
    //datatype_id（选传，现在要检查的对象的datatype的ID），id（必传，现在要检查的对象的ID）
    public function getPlatformOwnerGroup(){
        $repository=Zsystem::repository('group');
        $groups=$repository->get(0,1);
        if(!isset($groups)){
            return null;
        }
        if($groups->count()==0){
            return null;
        }
        return $groups[0];
    }

    public function getPlatformAdminGroup(){
        $repository=Zsystem::repository('group');
        $groups=$repository->get(1,1);
        if(!isset($groups)){
            return null;
        }
        if($groups->count()==0){
            return null;
        }
        return $groups[0];
    }

    public function getPublicGroup(){
        $repository=Zsystem::repository('group');
        $groups=$repository->get(2,1);
        if(!isset($groups)){
            return null;
        }
        if($groups->count()==0){
            return null;
        }
        return $groups[0];
    }

    public function isOwnerOfOwnerGroup($parameters){
        if(is_array($parameters['userId'])){
            foreach ($parameters['userId'] as $key=>$id){
                if($parameters['owner_id']!=$id){
                    return false;
                }
            }
            return true;
        }
        return ($parameters['owner_id']==$parameters['userId']);
    }

    //一个参数可能是一阶数组，另一个参数是单一元素
    public function itemEqual($array,$item){
        if(is_array($array)){
            foreach ($array as $key=>$id){
                if($item!=$id){
                    return false;
                }
            }
            return true;
        }
        return ($item==$array);
    }

    public function isCurrentGroup($parameters){
        if(!isset($parameters)){
            return false;
        }
        //当前组有没有？不管子组
        $repository=Zsystem::repository('datatype');
        $search['search'][]=['field'=>'id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(!isset($result)){
            return $result;
        }
        if(strtolower($result->name)=='group'||strtolower($result->name)=='groups'){
            if($parameters['id']==$parameters['groupId']){
                return true;
            }
        }
        return false;
    }

    //查看操作对象是不是在组内
    public function inGroup($parameters){
    //当前组有没有？不管子组
        $repository=Zsystem::repository('groupObject');
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        if(is_array($parameters['id'])){
            foreach ($parameters['id'] as $key=>$id){
                $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
                $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
                $result=$repository->fetch($search);
                if(!isset($result)){
                    return $result;
                }
            }
        }else{
            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->fetch($search);
            if(!isset($result)){
                return $result;
            }
        }
        return true;

}
    public function inFamilyGroup($parameters){
        //检查owner关系，不考虑分享关系
        //当前组有没有？
        //子组里有没有
        $repository=Zsystem::repository('groupFamily');
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];

        $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        return $result;
    }

    public function notInFamilyGroup($parameters){
        //当前组有没有？
        //子组里有没有
        $result=$this->inFamilyGroup($parameters);
        if($result){
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.failed');
        }else{
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');
        }
        return $messageResponse;
    }
    public function shouldInFamilyGroup($parameters){
        //当前组有没有？
        //子组里有没有
        $result=$this->inFamilyGroup($parameters);
        if($result){
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');

        }else{
            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.failed');
        }
        return $messageResponse;
    }

    public function isGroupMemberOwn($parameters){

    }
    //找出容器ID
    public function ContainerIds($parameters){
        //找出要检查的对象的类型ID
        $repository=Zsystem::repository('datatype');
        $datatypeId=$repository->key($parameters['slug']);
        //查看在不在第一组内，只要有一个对象不在第一组内，整个都算不在
        $repository=Zsystem::repository('groupObject');
        $search['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
        if(is_array($parameters['id'])){
            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'in','algorithm'=>'and'];
        }else{
            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
        }

        $result=$repository->index($search);
        if($result->count()>0){
            return $result->pluck('group_id')->distinct()->toArray();
        }
    }

    public function isGroupOwnerOrMember($groupId,$groupOwnerId,$userId){
        $result = $this->itemEqual( $userId,$groupOwnerId);
        if ($result) {
            return true;
        }
        $result = $this->inGroup( ['slug'=>'user','id'=>$userId,'groupId'=>$groupId]);
        if ($result) {
            return true;
        }
        return false;
    }

    //不支持多个ID
    public function ownerIdSet($slug,$id){
        $repository=Zsystem::repository($slug);
        $search['search'][]=['field'=>'id','value'=>$id,'filter'=>'=','algorithm'=>'or'];
        $result=$repository->fetch($search);
        $owner_group_id=null;
        $owner_id=null;
        if(isset($result)){
            if(isset($result->owner_group_id)) {
                $owner_group_id = $result->owner_group_id;
            }
            if(isset($result->owner_id)){
                $owner_id=$result->owner_id;

            }
        }
        return ['ownerGroupId'=>$owner_group_id,'ownerId'=>$owner_id];
    }

    //不是平台owner和平台admin的用户，通过本方法验证权限
    public function checkNoAdminPermission($parameters){
        //groupId为null？这个对象不在任何组内？是该用户拥有的吗？
        if(!isset($parameters['groupId'])){
            $idSet=$this->ownerIdSet($parameters['datatypeSlug'],$parameters['id']);
            if(!isset($idSet['ownerGroupId'])&&isset($idSet['ownerId'])){
                if($idSet['ownerId']==$parameters['userId']){
                    $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.success');
                    return $messageResponse;//有ownerGroupId，ownerId就无效了
                }
            }
        }

        //如果不是该用户拥有，该用户在表里有单个针对该对象的权限吗？group_user_object_permissions.
        $result=$this->checkGuop($parameters);
        if(isset($result)){
            if($result){
                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.success');
                return $messageResponse;//
            }else{
                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
                return $messageResponse;//
            }
        }
        //group_user_object_permissions表里没有数据，而groupId又为null，肯定该用户是没有权限的了
        if(!isset($parameters['groupId'])){
            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
            return $messageResponse;//
        }

        //如果group_user_object_permissions表里没有数据，需要看一看该用户的组角色是不是拥有权限
        //如果groupId不为null，除了添加到组的动作，该对象都必须在该组内（子组内）。如果不在，要报参数错误，因为提交前需要确认在组内？
        $result=$this->inGroup($parameters);
        if(!$result){
            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
            return $messageResponse;//
        }
        //该组有容纳该类型对象的属性吗？没有，报权限错误
        $result=$this->groupHasDatatype($parameters);
        if(!$result){
            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
            return $messageResponse;//
        }
        //这个动作依赖对象的属性吗？从组内移除，不依赖对象，
        if($this->isObjectAbility($parameters)){
            //该对象有对应的动作的属性吗？没有，报权限错误
            $result=$this->objectHasAbility($parameters);
            if(!$result){
                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
                return $messageResponse;//
            }
        }


        //找出用户的角色在当前组内拥有的最高权限
        $result=$this->checkGroupRoleObjectPermissions($parameters);

            if($result){
                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.success');
                return $messageResponse;//
            }else{
                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
                return $messageResponse;//
            }

        //该用户的角色的权限可以操作该类型对象吗？
        //该用户针对单个对象有哪些操作权限
        //该用户单个的权限可以操作该类型对象吗？
        //最后处理一下三元操作，从一个组分享一个对象到另一个组
    }

    //在当前组的整个父系中检查该用户的权限，只针对该用户的角色，该类型的数据对象
    //只有角色的权限可以被子代继承，一对一授权的权限无法被继承
    public function checkParentPermissions($groupId,$userId,$datatypeId,$actionId){
        //找出这个组的全部父组
        $repository=Zsystem::repository('groupParent');
        $search['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $groupIds=$result->pluck('parent_id')->toArray();
        }
        $groupIds[]=$groupId;
        //首先找到该用户在这些组的全部角色
        $repository=Zsystem::repository('groupUserRole');
        unset($search);
        $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
//        $search['search'][]=['field'=>'group_id','value'=>[5],'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'user_id','value'=>$userId,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $roleSet=[];
            //同时剔除已经时间无效的角色
            foreach ($result as $key=>$item){
                if((($item->schedule_begin==null)&&($item->schedule_end==null))||($item->schedule_begin<time()&&$item->schedule_end>time())){
                    $roleSet[]=$item->id;
                }

            }
//            $roleIdSet=$result->pluck('role_id')->toArray();
        }else{
            return false;
        }

        //这些角色有哪些权限
        $repository=Zsystem::repository('groupRolePermission');
        unset($search);
//        $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'gur_id','value'=>$roleSet,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$actionId,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);//源组的操作权限全部取出来了
        return $result;
    }
    public function checkGroupRoleObjectPermissions($parameters){
        //首先找到该组的全部ownerparent，该组为最后一个
        $result=$this->checkParentPermissions($parameters['groupId'],$parameters['userId'],$parameters['datatypeId'],$parameters['actionId']);
        if(!isset($result)){
            return $result;
        }

        if(isset($parameters['destinationGroupId'])){
            $result=$this->checkParentPermissions($parameters['destinationGroupId'],$parameters['userId'],
                $parameters['datatypeId'],$parameters['destinationActionId']);
            //目的组及其parent
        }
        return $result;
    }

    //用户可以不在当前组，仅仅是对当前组的某个对象拥有权力
    public function checkGuop($parameters){
        //最后处理一下三元操作，从一个组分享一个对象到另一个组
        if(!isset($parameters['id'])){
            return false;
        }
        $repository=Zsystem::repository('guop');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(isset($result)){
            //检查时间有效性
            $scheduleBegin=$result->schedule_begin;
            $scheduleEnd=$result->schedule_end;
            if($scheduleBegin==null&&$scheduleEnd==null){
                return true;
            }
            if($scheduleBegin<time()&&$scheduleEnd>time()){
                return true;
            }
        }
//        if(isset($parameters['destinationGroupId'])){
//            unset($search);
//            $search['search'][]=['field'=>'group_id','value'=>$parameters['destinationGroupId'],'filter'=>'=','algorithm'=>'and'];
//            $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
//            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
//            $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
//            $search['search'][]=['field'=>'action_id','value'=>$parameters['destinationActionId'],'filter'=>'=','algorithm'=>'and'];
//            $result=$repository->fetch($search);
//            return $result;
//        }
        return $result;
    }

    public function groupHasDatatype($parameters){
        $repository=Zsystem::repository('groupDatatype');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        return $result;
    }

    public function isObjectAbility($parameters){
        //object可以设置其在某个组内的许可动作，但是，有一些动作与object无关
        //如移除出组，而增加减少对象的许可动作，仅仅受最初创建者给的动作限制，不能超过创建者给的各种许可动作
        //转让owner，也和对象无关，不能设置这个动作的限制
        //还有一些动作只能对组使用
        $repository=Zsystem::repository('action');
        $search['search'][]=['field'=>'id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'depend_object','value'=>1,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        return $result;
    }

    //一个对象在一个容器里，会给予动作限制，可以做哪些动作，不能做哪些动作
    public function objectHasAbility($parameters)
    {
        $repository=Zsystem::repository('objectAction');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
        //enabled用来某项操作，比如，咨询卡已经使用过了，那么，在这个组里，就不能再次消费这个卡，
        $search['search'][]=['field'=>'enabled','value'=>1,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(isset($result)){
            return true;
        }
        return false;
    }

    public function isPlatformOwnerMember($slug,$userId){
        $ownerGroup=$this->getPlatformOwnerGroup();
        if(!isset($ownerGroup)){
            $messageResponse=$this->messageResponse($slug,  'authorize.validation.failed');
            return $messageResponse;
        }

        //如果要检查的对象是用户类型，可以看看这个用户是不是第一组的owner，
        //owner是一对一的，任何一个ID不是owner，都为错
        $result = $this->isGroupOwnerOrMember( $ownerGroup->id,$ownerGroup->owner_id,$userId);
        if ($result) {
            $messageResponse = $this->messageResponse($slug, 'authorize.submit.success');
            return $messageResponse;
        }
        $messageResponse = $this->messageResponse($slug, 'authorize.submit.failed');
        return $messageResponse;
    }

//    public function inPlatformOwner($parameters){
//        //第一组，第二组必然存在，丢失则报错
//        //查看该用户是第一组的owner还是成员
//        $ownerGroup=$this->getPlatformOwnerGroup();
//        if(!isset($ownerGroup)){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//            return $messageResponse;
//        }
//
//        if($parameters['datatypeSlug']=='user'){
//            //如果要检查的对象是用户类型，可以看看这个用户是不是第一组的owner，
//            //owner是一对一的，任何一个ID不是owner，都为错
//            $array=$parameters['id'];
//            $item=$ownerGroup->owner_id;
//            $result = $this->itemEqual( $array,$item);
//            if ($result) {
//                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.success');
//                return $messageResponse;
//            }
//        }
//
//        //这些数据对象没有group_owner_id,也没有owner_id的，都只能默认归平台owner所有了
//        $repository=Zsystem::repository($parameters['datatypeSlug']);
//        $search['search'][]=['field'=>'id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'or'];
//        $result=$repository->index($search);
//        if(!isset($result)){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
//            return $messageResponse;//必然应该有返回值，这是错误
//        }
//        if($result->count()==0){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
//            return $messageResponse;//必然应该有返回值，这是错误
//        }
//        $ownerGroupId=$result->pluck('owner_group_id')->distinct()->toArray();
//        if(emptyObjectOrArray($ownerGroupId)){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.success');
//            return $messageResponse;//
//        }
//        }
//    }
//        if(is_array($parameters['id'])){
//            foreach ($parameters['id'] as $key=>$id){
//
//        //第一组装载了这些数据对象吗？
//        $result=$this->inGroup(['group_id'=>$ownerGroup->id,'datatypeId'=>$parameters['datatypeId'],'id'=>$parameters['id']]);
//        if($result){
//            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.success');
//            return $messageResponse;
//        }
//
//        if($parameters['datatypeSlug']!='user') {
//            //第一组的成员单独拥有这些数据对象吗？
//            $result = $this->isGroupMemberOwn(['group_id' => $ownerGroup->id, 'datatypeId' => $parameters['datatypeId'], 'id' => $parameters['id']]);
//            if ($result) {
//                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.success');
//                return $messageResponse;
//            }
//        }
//
//        else{
//            //不是用户类型的数据对象，都有一个owner_group_id参数，如果owner_group_id不是第一组，就返回false
//            //不是用户类型的数据对象，都有一个owner_id参数，如果owner_id不是第一组的成员，就返回false
//            //先把这些数据对象的完整数据集取出，里面有owner_group_id和owner_id
//            $repository=Zsystem::repository($parameters['datatypeSlug']);
//            if(is_array($parameters['id'])){
//                foreach ($parameters['id'] as $key=>$id){
//                    $search['search'][]=['field'=>'id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'or'];
//                    $result=$repository->fetch($search);
//                    if(isset($result)){
//                        if(isset($result->owner_group_id)){
//                            if($result->owner_group_id!=$ownerGroup->id){
//                                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
//                                return $messageResponse;//只要有一个的ownergroup不是平台owner组，就为false
//                            }
//                        }
//
//                    }
//                }
//
//            }else{
//                $search['search'][]=['field'=>'id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'or'];
//                $result=$repository->fetch($search);
//                if(isset($result)){
//                    if(isset($result->owner_group_id)){
//                        if($result->owner_group_id!=$ownerGroup->id){
//                            $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.failed');
//                            return $messageResponse;//只要有一个的ownergroup不是平台owner组，就为false
//                        }
//                    }
//
//                }
//            }
//            $result=$repository->index($search);
//
//            $array=$parameters['id'];
//            $item=$ownerGroup->owner_id;
//            $result = $this->itemEqual( $array,$item);
//            if ($result) {
//                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.validation.success');
//                return $messageResponse;
//            }
//        }
//
//
//
//
//
//        //如果用户不是owner，还要检查是不是在第一组内，也可以具有第一组的特权
//        $ids=$this->ContainerIds(['datatypeSlug'=>'user','id'=>$parameters['id']]);
//
//
//
//
//
//
//
//        //如果要检查的对象是用户类型，这个用户虽然不是第一组的owner，但如果在第一组容器内，也可以具有第一组的特权
//        //member是一对多的，一个用户可以在多个组，有一个组是owner组，都为真
//
//        //如果数据对象不是第一组的owner，那么，这个对象被那些组装载？
//        $result=$this->ContainerIds(['ownerGroupId'=>$ownerGroup->id,'slug'=>$parameters['datatypeSlug'],'id'=>$parameters['id']]);
//        if(isset($result)){
//            //这些id有没有不是第一组的
//            if(count($result)>1){
//                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//                return $messageResponse;//只要多于一个组，就证明这个数据对象被多个组装载
//            }
//            if($result[0]==$ownerGroup->id){
//                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.success');
//                return $messageResponse;
//            }
//        }
//        //不在任何容器/组里
//        if($result->count()>0){
//            //有没有不是第一组
//            if(is_array($parameters['id'])){
//                if($result->count()==count($parameters['id'])){
//                    $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.success');
//                    return $messageResponse;
//                }
//            }else{
//                if($result->count()==1){
//                    $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.success');
//                    return $messageResponse;
//                }
//            }
//            if($result->count()==1){
//                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//                return $messageResponse;
//            }
//        }
//
//
//
//
//        //如果数据对象不在任何一组，是否是第一组的组成员单独拥有这个数据对象
//        unset($search);
//        $search['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
//        if(is_array($parameters['id'])){
//            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'in','algorithm'=>'and'];
//        }else{
//            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
//        }
//        $result=$repository->fetch($search);
//        if(!emptyObjectOrArray($result)){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.failed');
//            return $messageResponse;
//        }
//        unset($search);
//        $repository=Zsystem::repository('userObject');
//        $search['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
//        if(is_array($parameters['id'])){
//            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'in','algorithm'=>'and'];
//        }else{
//            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
//        }
//        $result=$repository->index($search);
//        if(emptyObjectOrArray($result)){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');
//            return $messageResponse;//如果没有任何人拥有这个数据对象,只能是owner有权限处理
//        }
//        if($result->count()==0){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');
//            return $messageResponse;//如果没有任何人拥有这个数据对象,只能是owner有权限处理
//        }
//        $userIds=$result->pluck('user_id')->toArray();
//        $result=$this->inPlatformOwner(['slug'=>$parameters['slug'],'datatype_slug'=>'user','id'=>$userIds]);
//            //如果这个数据对象不在任何组内，查看第一组的组成员是否拥有这个数据对象
////        $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.success');
//        return $result;
////        }
//    }
//
//    public function platformAdminCanDo($parameters){
//        //没有第一第二组，报错
//        $repository=Zsystem::repository('group');
//        $groups=$repository->get(0,2);
//        if(isset($groups)){
//            if($groups->count()<2){
//                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//                return $messageResponse;
//            }
//            if($groups[0]->id==$parameters['groupId']){
//                $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//                return $messageResponse;
//            }
//        }
//
//        //当前用户是在第二组吗？不在第二组不能操作
//        $repository=Zsystem::repository('datatype');
//        $userTypeId=$repository->key('user');
//        $groupId=$groups[1]->id;
//        $repository=Zsystem::repository('groupObject');
//        $search['search'][]=['field'=>'datatype_id','value'=>$userTypeId,'filter'=>'=','algorithm'=>'and'];
//        $search['search'][]=['field'=>'group_id','value'=>$groupId,'filter'=>'=','algorithm'=>'and'];
//        $search['search'][]=['field'=>'object_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
//        $result=$repository->fetch($search);
//        if(!isset($result)){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.submit.failed');
//            return $messageResponse;
//        }
//        //当前操作对象是不是被第一组owner？或者是仅仅被第一组成员所onwer吗？
//        //个人owner和组owner，如果有组owner，则无视个人owner，
//        $slug=$parameters['slug'];
//        $repository=Zsystem::repository($slug);
//        $id=$parameters['id'];
//        if(is_array($id)){
//            foreach ($id as $key=>$item){
//                $search['search'][]=['field'=>id,'value'=>$item,'filter'=>'=','algorithm'=>'or'];
//            }
//        }else{
//            $search['search'][]=['field'=>id,'value'=>$parameters['id'],'filter'=>'=','algorithm'=>'or'];
//        }
//
//        $result=$repository->index($search);
//        if(!isset($result)){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//            return $messageResponse;//操作对象必须存在
//        }
//        if($result->count()==0){
//            $messageResponse=$this->messageResponse($parameters['slug'],  'authorize.validation.failed');
//            return $messageResponse;//操作对象必须存在
//        }
//        //找出操作对象的owner,有一个人在第一组，就不能操作
//        $owner_ids=$result->pluck('owner_id')->toArray();
//        if($owner_ids->count()>0){
//            if(count($owner_ids)>1){
//                $search['search'][]=['field'=>'object_id','value'=>$owner_ids,'filter'=>'in','algorithm'=>'and'];
//            }else{
//                $search['search'][]=['field'=>'object_id','value'=>$owner_ids[0],'filter'=>'=','algorithm'=>'and'];
//            }
//            $search['search'][]=['field'=>'group_id','value'=>$groups[0]->id,'filter'=>'=','algorithm'=>'and'];
//            $search['search'][]=['field'=>'datatype_id','value'=>$userTypeId,'filter'=>'=','algorithm'=>'and'];
//        }
//        if(isset($result)) {
//            if ($result->count() == 0) {
//                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
//                return $messageResponse;//有owner是第一组成员
//            }
//        }
//
//        //找出操作对象的owner组,只要有一个是第一组，就不能操作
//        $owner_group_ids=$result->pluck('owner_group_id')->toArray();
//        if($owner_group_ids->count()>0){
//            if(count($owner_group_ids)>1){
//                $search['search'][]=['field'=>'object_id','value'=>$owner_group_ids,'filter'=>'in','algorithm'=>'and'];
//            }else{
//                $search['search'][]=['field'=>'object_id','value'=>$owner_group_ids[0],'filter'=>'=','algorithm'=>'and'];
//            }
//            $search['search'][]=['field'=>'group_id','value'=>$groups[0]->id,'filter'=>'=','algorithm'=>'and'];
//            $search['search'][]=['field'=>'datatype_id','value'=>$userTypeId,'filter'=>'=','algorithm'=>'and'];
//        }
//        if(isset($result)) {
//            if ($result->count() == 0) {
//                $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.failed');
//                return $messageResponse;//有owner是第一组成员
//            }
//        }
//        $messageResponse = $this->messageResponse($parameters['slug'], 'authorize.submit.success');
//        return $messageResponse;//有owner是第一组成员
//    }
}