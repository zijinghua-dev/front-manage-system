<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Illuminate\Http\Request;
use Zijinghua\Zbasement\Http\Controllers\BaseController;

class RoleController extends BaseController
{
    public function authorize(Request $request){
        return  $this->execute($request,'authorize');
    }
}