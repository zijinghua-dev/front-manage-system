<?php


namespace Zijinghua\Zvoyager\Http\Repositories;


use Zijinghua\Zvoyager\Http\Contracts\UserRepositoryInterface;
use Zijinghua\Zbasement\Http\Repositories\BaseRepository;

class RestfulUserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function fetch($data){
        $model=$this->model($this->getSlug());
        return $model->fetch($data);
    }
}