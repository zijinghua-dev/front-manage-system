<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Zijinghua\Zbasement\Http\Requests\IndexRequest;

class PermissionController extends BaseGroupController
{
    public function index(IndexRequest $request){
        return $this->execute($request,'index');
    }
}