<?php


namespace Zijinghua\Zvoyager\Http\Controllers;



use Illuminate\Http\Request;
use Zijinghua\Zbasement\Http\Controllers\BaseController;
use Zijinghua\Zbasement\Http\Requests\ClearRequest;
use Zijinghua\Zbasement\Http\Requests\StoreRequest;


class GroupController extends BaseController
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

    //组的创建是特殊的，必须要有父组
    public function store(StoreRequest $request){
        return $this->execute($request,'store');
    }
}