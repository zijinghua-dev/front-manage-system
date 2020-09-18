<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Illuminate\Http\Request;
use Zijinghua\Zbasement\Http\Controllers\BaseController;

class RoleController extends BaseController
{
    public function assign(Request $request){
        return  $this->execute($request,'assign');
    }

    public function setup(Request $request){
        return  $this->execute($request,'setup');
    }
}