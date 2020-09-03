<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeServiceInterface;

class DatatypeService extends BaseGroupService implements DatatypeServiceInterface
{
    //输入参数：userId，groupId
    //用户可以看到/操作哪些数据类型
    public function index($parameters){
        //平台管理员/admin可以操作所有数据类型
        //普通用户要经过guop和gup查询其可操作的数据类型，根据groupId不同
        //获取用户的角色，查看是不是平台管理员/admin
        $service=Zsystem::service('authorize');
        $result=$service->isPlatformOwner($parameters['userId']);
        if(!isset($result)){
            $messageResponse=$this->messageResponse(null,'authorize.validation.failed');
            return $messageResponse;
        }
        if(!$result){
            $result=$service->isPlatformAdmin($parameters['userId']);
            if(!isset($result)){
                $messageResponse=$this->messageResponse(null,'authorize.validation.failed');
                return $messageResponse;
            }
        }
        if($result){
            if(!isset($parameters['groupId'])){
                $repository=$this->repository('datatype');
                $result= $repository->index();
                $messageResponse=$this->messageResponse(null,'authorize.submit.success',$result);
                return $messageResponse;
            }
        }
        if(!isset($parameters['groupId'])){
            $messageResponse=$this->messageResponse(null,'authorize.validation.failed');
            return $messageResponse;
        }

        $repository=$this->repository('groupUserRole');
        $search['search'][]=['field'=>'group_id','value'=>$parameters['groupId'],'filter'=>'=','algorithm'=>'and'];
        $search['search'][]=['field'=>'user_id','value'=>$parameters['userId'],'filter'=>'=','algorithm'=>'and'];
        $result=$repository->index($search);

        $dataSet=new Collection();
        foreach ($result as $key=>$item){
            $repository=$this->repository('groupRolePermission');
            unset($search);
            $search['search'][]=['field'=>'group_id','value'=>$item->group_id,'filter'=>'=','algorithm'=>'and'];
            $search['search'][]=['field'=>'role_id','value'=>$item->role_id,'filter'=>'=','algorithm'=>'and'];
            $grp=$repository->index($search);
            $dataSet=$grp->merge($dataSet);
        }
        $datatypeIds=[];
        if($dataSet->count()>0){
            $datatypeIds=$dataSet->pluck('datatype_id')->toArray();
        }
        unset($search);
        $search['search'][]=['field'=>'id','value'=>$datatypeIds,'filter'=>'in','algorithm'=>'or'];
        $repository=$this->repository('datatype');
        $result= $repository->index($search);
        $datatypeWithAction=$result->map(function ($datatype) use($dataSet){
            $datatype['action'] = $dataSet->where('datatype_id',$datatype->id)->pluck('action_id')->toArray();
            return $datatype;
        });
        $total_count = $datatypeWithAction->count();
        $limit = 20;
        $current_page = request()->get('page');
        $options = [
            'path' => request()->url(),
            'query' => request()->query(),
        ];
        $paginated_collection = new LengthAwarePaginator($datatypeWithAction, $total_count, $limit, $current_page, $options);
        $messageResponse=$this->messageResponse(null,'authorize.submit.success',$paginated_collection);
        return $messageResponse;
    }
}