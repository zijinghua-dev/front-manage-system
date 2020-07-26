<?php

namespace Zijinghua\Zvoyager\Http\Controllers;

use Zijinghua\Zbasement\Http\Requests\ClearRequest;
use Zijinghua\Zbasement\Http\Traits\Slug;
use Zijinghua\Zvoyager\Http\Requests\LoginRequest;
use Zijinghua\Zbasement\Events\Api\InterfaceAfterEvent;
use Zijinghua\Zbasement\Events\Api\InterfaceBeforeEvent;
use Zijinghua\Zbasement\Http\Controllers\BaseController as BaseController;

class DatatypeController extends BaseController
{
    //从组内移除，并不删除
//    public function clear(ClearRequest $request){
//
//            $this->setSlug('groupDataType');
//
//        return  parent::clear($request);
//    }
}