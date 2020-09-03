<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Zijinghua\Zbasement\Http\Requests\IndexRequest;

class MenuController extends BaseGroupController
{
    //userId,groupId,menuDatatypeId
    public function index(IndexRequest $request)
    {
        return  $this->execute($request,'index');
    }
}