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

    public function checkAdminPermission($parameters){
        //如果组ID为1，只检查该用户是否在该组内，不用检查对象是否在该组内
        //如果组ID为2，只检查该用户是否在该组内，以及操作对象是否是在1组内，只要不是1组成员，都可以操作
        $userService=Zsystem::service('user');
        $groupIds=$userService->groupIds($parameters['userId']);
        if(in_array(1,$groupIds)){
            return true;
        }elseif(in_array(2,$groupIds)){
           //现在要检查操作对象是不是1组的
            if($parameters['groupId']>1){
                return true;
            }
        }
        return false;
    }

    public function checkUserPermission($parameters){
        //改变组的属性，想要添加datatype的时候，必须父组有这个datatype
        $groupService=Zsystem::service('group');
        $parent=true;
        while (isset($parent)){
            //首先是当前组的权限
            $permissions=$this->permissions($parameters);
            //再找父组
            //查看有没有权限，没有继续找
            $result=$permissions->where('action_id',$parameters['actionId'])->where('datatype_id',$parameters['dataTypeId']);
            if($result->count()>0){

                return true;
            }

            //现在查看有没有父组
            $parent=$groupService->parent($parameters['groupId']);
            if(isset($parent)){
                //把当前组改成父组
                $parameters['groupId']=$parent->group_id;
            }
        }
        return false;
    }

    public function checkPermission($parameters){
        //如果组ID为null，检查是不是该用户自己创建的对象
        //如果组ID为1，只检查该用户是否在该组内，不用检查对象是否在该组内
        //如果组ID为2，只检查该用户是否在该组内，以及操作对象是否是在1组内，只要不是1组成员，都可以操作
//        $userId,$groupId,$dataTypeId,$objectId,$actionId
        $result=$this->checkAdminPermission($parameters);
        if($result){
            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.success');
            return $messageResponse;
        }
        $result=$this->checkUserPermission($parameters);
        if($result){
            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.success');
            return $messageResponse;
        }

            $messageResponse=$this->messageResponse($parameters['slug'],'authorize.failed');
            return $messageResponse;

    }

    //当前用户是不是第一组成员？
    //当前操作对象是否在第一组？
    //当前操作对象是否是第一组成员拥有？
    //当前组或者是目的组，是不是第一组？
    //传入参数：slug(必传，调用的slug)，datatype_slug（必传，现在要检查的对象的datatype），
    //datatype_id（选传，现在要检查的对象的datatype的ID），id（必传，现在要检查的对象的ID）
    public function getPlatformOwnerGroup($slug){
        $repository=Zsystem::repository('group');
        $groups=$repository->get(0,1);
        if(!isset($groups)){
            $messageResponse=$this->messageResponse($slug,  'authorize.validation.failed');
            return $messageResponse;
        }
        if($groups->count()==0){
            $messageResponse=$this->messageResponse($slug,  'authorize.validation.failed');
            return $messageResponse;
        }
        $messageResponse=$this->messageResponse($slug,  'authorize.validation.success',$groups);
        return $messageResponse;
    }

    public function getPlatformAdminGroup($slug){
        $repository=Zsystem::repository('group');
        $groups=$repository->get(1,1);
        if(!isset($groups)){
            $messageResponse=$this->messageResponse($slug,  'authorize.validation.failed');
            return $messageResponse;
        }
        if($groups->count()==0){
            $messageResponse=$this->messageResponse($slug,  'authorize.validation.failed');
            return $messageResponse;
        }
        $messageResponse=$this->messageResponse($slug,  'authorize.validation.success',$groups);
        return $messageResponse;
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
                    return false;
                }
            }
        }else{
            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->fetch($search);
            if(!isset($result)){
                return false;
            }
        }
        return true;

}
    public function inFamilyGroup($parameters){
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

    public function checkParentPermissions($groupId,$userId,$datatypeId,$objectId,$actionId){
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
        $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'user_id','value'=>$userId,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);
        if($result->count()>0){
            $roleIdSet=$result->pluck('role_id')->toArray();
        }else{
            return null;
        }

        //这些角色有哪些权限
        $repository=Zsystem::repository('groupRolePermission');
        unset($search);
        $search['search'][]=['field'=>'group_id','value'=>$groupIds,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'role_id','value'=>$roleIdSet,'filter'=>'in','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$datatypeId,'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$actionId,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);//源组的操作权限全部取出来了
        return $result;
    }
    public function checkGroupRoleObjectPermissions($parameters){
        //首先找到该组的全部ownerparent，该组为最后一个
        $result=$this->checkParentPermissions($parameters['groupId'],$parameters['userId'],$parameters['datatypeId'],$parameters['id'],$parameters['actionId']);
        if(!isset($result)){
            return false;
        }

        if(isset($parameters['destinationGroupId'])){
            $result=$this->checkParentPermissions($parameters['destinationGroupId'],$parameters['userId'],
                $parameters['datatypeId'],$parameters['id'],$parameters['destinationActionId']);
            //目的组及其parent
        }
        return $result;
    }

    public function checkGuop($parameters){
        //最后处理一下三元操作，从一个组分享一个对象到另一个组
        $repository=Zsystem::repository('guop');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        if(isset($parameters['destinationGroupId'])){
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$parameters['destinationGroupId'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'action_id','value'=>$parameters['destinationActionId'],'filter'=>'=','algorithm'=>'and'];
            $result=$repository->fetch($search);
            return $result;
        }
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

    public function objectHasAbility($parameters)
    {
        $repository=Zsystem::repository('objectAction');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'datatype_id','value'=>$parameters['datatypeId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'object_id','value'=>$parameters['id'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'action_id','value'=>$parameters['actionId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'enabled','value'=>1,'filter'=>'=','algorithm'=>'and'];
        $result=$repository->fetch($search);
        return $result;
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