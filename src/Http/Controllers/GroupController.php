<?php


namespace Zijinghua\Zvoyager\Http\Controllers;


use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Zijinghua\Zbasement\Http\Controllers\BaseController;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;

class GroupController extends BaseController
{
    public function show(ShowRequest $request){
        $ability=Gate::abilities();
        $policy=Gate::policies();
//        if(Gate::has('users_browse')){
//            return ;
//        }
        if(Gate::allows('users_browse')){
            return ;
        }
        return ;
    }
}