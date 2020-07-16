<?php


namespace Zijinghua\Zvoyager\Http\Models;


use Zijinghua\Zvoyager\Http\Contracts\UserModelInterface;

class RestfulUser extends ResfulModel implements UserModelInterface
{
    public function fetch($data){
//        $host=getConfigValue('zvoyager');
//        $host=getConfigValue('zvoyager.usercenter');
        $host=getConfigValue('zvoyager.usercenter.host');

        $fetchUri=getConfigValue('zvoyager.usercenter.api.fetch.uri');
        $action=getConfigValue('zvoyager.usercenter.api.fetch.action');
        $fetchUri=$host.$fetchUri;
//        $parameters=$data;
        $data=$this->connect($action,$fetchUri,$data);
        return $data;
    }
}