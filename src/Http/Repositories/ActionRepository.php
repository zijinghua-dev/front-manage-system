<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\ActionRepositoryInterface;

class ActionRepository extends BaseRepository implements ActionRepositoryInterface
{
    public function key($name){
        //输入参数为字符串，则表明调用者不清楚字段，则需要在name，alias同时查询
        if(is_array($name)){
            return parent::key($name);
        }else{
            if(is_string($name)){
                $parameter['search'][]=['field'=>'name','value'=>$name];
                $parameter['search'][]=['field'=>'alias','value'=>$name];
                return parent::key($parameter);
            }
        }
    }
}