<?php

namespace Zijinghua\Zvoyager\Http\Controllers;

use Zijinghua\Zbasement\Http\Requests\ClearRequest;
use Zijinghua\Zbasement\Http\Requests\IndexRequest;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;
use Zijinghua\Zbasement\Http\Traits\Slug;
use Zijinghua\Zvoyager\Http\Requests\LoginRequest;
use Zijinghua\Zbasement\Events\Api\InterfaceAfterEvent;
use Zijinghua\Zbasement\Events\Api\InterfaceBeforeEvent;
use Zijinghua\Zbasement\Http\Controllers\BaseController as BaseController;

class DatatypeController extends BaseGroupController
{
    //可以看到哪些数据对象
    public function index(IndexRequest $request){
        return $this->execute($request,'index');
    }

    public function show(ShowRequest $request){
        return $this->execute($request,'show');
    }
}