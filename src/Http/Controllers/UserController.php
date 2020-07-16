<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Zijinghua\Zbasement\Http\Controllers\BaseController as BaseController;
use Zijinghua\Zbasement\Http\Requests\IndexRequest;
use Zijinghua\Zbasement\Http\Requests\ShowRequest;

class UserController extends BaseController
{
    public function updatePassword(UpdatePasswordRequest $request){
        $response=$this->execute($request,'updatePassword');
        return $response;
    }

    public function show(ShowRequest $request){
        $response=$this->execute($request,'show');
        return $response;
    }
    public function index(IndexRequest $request){
        $response=$this->execute($request,'index');
        return $response;
    }
}
