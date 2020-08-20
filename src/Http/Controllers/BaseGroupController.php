<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Illuminate\Http\Request;
use Zijinghua\Zbasement\Http\Controllers\BaseController;

class BaseGroupController extends BaseController
{
    public function mine(Request $request){
        return $this->execute($request,'mine');
    }

    public function run(Request $request){
        return $this->execute($request,'run');
    }
}