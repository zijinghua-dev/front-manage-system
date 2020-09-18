<?php


namespace Zijinghua\Zvoyager\Http\Controllers;



use Illuminate\Http\Request;
use Zijinghua\Zbasement\Http\Controllers\BaseController;
use Zijinghua\Zbasement\Http\Requests\ClearRequest;
use Zijinghua\Zbasement\Http\Requests\IndexRequest;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;
use Zijinghua\Zbasement\Http\Requests\StoreRequest;


class OrganizeController extends BaseGroupController
{
    //从组内移除，并不删除
    public function clear(ClearRequest $request){
        return  $this->execute($request,'clear');
    }
    //向组内添加对象
    public function append(Request $request){
        return  $this->execute($request,'append');
    }

    public function expand(Request $request){
        return $this->execute($request,'expand');
    }
    public function shrink(Request $request){
        return $this->execute($request,'shrink');
    }

    //组的创建。不能创建默认组,default!=null。
    //输入参数。必传：name;userId（写入到creator_id）；可选：groupId，可能为null；default，如果有，必须为null;picture;describe
    public function store(StoreRequest $request){
        return $this->execute($request,'store');
    }

    public function share(Request $request){
        return $this->execute($request,'share');
    }

    public function show(ShowRequest $request){
        return $this->execute($request,'show');
    }
    public function index(IndexRequest $request){
        return $this->execute($request,'index');
    }
    public function search(Request $request){
        return $this->execute($request,'search');
    }

}