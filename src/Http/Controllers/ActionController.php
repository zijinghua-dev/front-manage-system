<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Zijinghua\Zbasement\Http\Requests\IndexRequest;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;

class ActionController extends BaseGroupController
{
    //可以看到哪些方法：输入参数userId，datatypeId
    public function index(IndexRequest $request){
        return $this->execute($request,'index');
    }

    public function show(ShowRequest $request){
        return $this->execute($request,'show');
    }
}