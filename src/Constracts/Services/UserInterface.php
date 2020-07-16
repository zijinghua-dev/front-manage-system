<?php

namespace Zijinghua\Zvoyager\App\Constracts\Services;

interface UserInterface
{
    /**
     * 搜索用户
     * @param $params   关键字有usename,mobile,email,wechat_id,account
     * @return mixed
     */
    public function search($params);
    /**
     * 用户登录
     * @param $credentials  参数为usename,mobile,email,wechat_id,account以及可能会有password
     * @return mixed
     */
    public function login($credentials);
    /** 用户明细 */
    public function userinfo($params);

}
