<?php

namespace Zijinghua\Zvoyager\App\Services;

use Illuminate\Support\Str;
use Zijinghua\Zvoyager\App\Constracts\Repositories\UserInterface;
use Zijinghua\Zvoyager\App\Constracts\Services\UserInterface as UserServiceInterface;

class UserService implements UserServiceInterface
{
    protected $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }
    /**
     * 搜索用户
     * @param $params   关键字包括username,mobile,email,wechat_id,account
     * @return mixed
     */
    public function search($params)
    {
        //只能是'email','wechat_id','email','mobile','account'五种单一输入
        if (empty($params) ||
            (count($params) === 1 &&
                Str::contains($this->firstCredentialKey($params), 'password'))) {
            return;
        }
        return $this->user->search($params);
    }
    /**
     * 用户登录
     * @param $credentials   登录用户参数，包括username,mobile,email,wechat_id,account
     * @return mixed
     */
    public function login($credentials)
    {
        return $this->user->login($credentials);
    }
    /**
     * 获取用户信息
     * @param $params
     * @return mixed
     */
    public function userinfo($params)
    {
        return $this->user->login($params);
    }
    /**
     * Get the first key from the credential array.
     *
     * @param  array  $credentials
     * @return string|null
     */
    protected function firstCredentialKey(array $credentials)
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }
    }
}
