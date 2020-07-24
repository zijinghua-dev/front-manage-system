<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Illuminate\Support\Str;
use Zijinghua\Zbasement\Facades\Zsystem;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;
use Zijinghua\Zvoyager\Http\Contracts\DataTypeRepositoryInterface;

class DataTypeRepository extends BaseRepository implements DataTypeRepositoryInterface
{
    public function key($slugName){
        //数据库里是复数形式，为什么啊！！！！！！！！
        //如果调用者不想处理参数转换，可以直接把slug传进来，这里进行单复数，大小写转换
        //如果传入了数组，表明参数已经转换完毕
        if(is_array($slugName)){
            $parameter=$slugName;
        }else{
            $pSlugName=Str::plural($slugName);
            $pSlugName=strtolower($pSlugName);
            $parameter['search'][]=['field'=>'slug','value'=>$pSlugName];
        }

        return parent::key($parameter);
    }
}