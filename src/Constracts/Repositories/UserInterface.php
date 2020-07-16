<?php

namespace Zijinghua\Zvoyager\App\Constracts\Repositories;

interface UserInterface
{
    /** 搜索用户，关键字有usename,mobile,email,wechat_id,account */
    public function search($params);
    /** 用户登录 */
    public function login($credentials);
    /** 用户明细 */
    public function userinfo($params);

}
