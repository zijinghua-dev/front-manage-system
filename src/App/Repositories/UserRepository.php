<?php

namespace Zijinghua\Zvoyager\App\Repositories;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Zijinghua\Zvoyager\App\Constracts\Repositories\UserInterface;
use App\Models\User;

class UserRepository implements UserInterface
{
    protected $client;

    public function __construct()
    {
        // 让GuzzleHttp忽略错误
        $this->client = new Client(['http_errors' => false]);
    }

    /**
     * 搜索用户
     * @param $params   关键字包括username,mobile,email,wechat_id,account
     * @return mixed
     */
    public function search($params)
    {
        $query=[];
        foreach ($params as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }
            $query[$key]=$value;
        }
        $searchUri = config('zvoyager.usercenter.host') . config('zvoyager.usercenter.api.search_uri');

        $response = $this->client->request('get', $searchUri, [
            'query' => $query
        ]);
        $result = json_decode($response->getBody()->__toString(),true);
        $code = $response->getStatusCode();
        if ($code != 200) {
            return $result;
        }
        $user = app(User::class)->firstOrNew($result['data'][0]);
        return $user;
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