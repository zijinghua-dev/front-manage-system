<?php

namespace Zijinghua\Zvoyager\App\Repositories;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Zijinghua\Zvoyager\App\Constracts\Repositories\UserInterface;
use Zijinghua\Zvoyager\App\Models\User;

class UserRepository implements UserInterface
{
    protected $client;

    protected $user;

    public function __construct(User $user)
    {
        // 让GuzzleHttp忽略错误
        $this->client = new Client(['http_errors' => false]);
        $this->user = $user;
    }

    /**
     * 搜索用户
     * @param $params   关键字包括username,mobile,email,wechat_id,account
     * @return mixed
     */
    public function search($params)
    {
        return $this->user->search($params);
    }

    /**
     * 用户登录
     * @param $credentials   登录用户参数，包括username,mobile,email,wechat_id,account
     * @return mixed
     */
    public function login($credentials)
    {
        $loginUri = config('zvoyager.usercenter.host') . config('zvoyager.usercenter.api.login_uri');
        $requestData = [
            'form_params' => $credentials
        ];

        $response = $this->client->request('post', $loginUri, $requestData);
        return json_decode($response->getBody()->__toString(),true);
    }

    /**
     * 获取用户信息
     * @param $params
     * @return mixed
     */
    public function userinfo($params)
    {
        $loginUri = config('zvoyager.usercenter.host') .config('zvoyager.usercenter.api.detail_uri') . "/{$params}";

        $response = $this->client->request('get', $loginUri);
        $result = json_decode($response->getBody()->__toString(),true);
        return app(User::class)->firstOrNew($result['data']);
    }
}