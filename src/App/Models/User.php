<?php

namespace Zijinghua\Zvoyager\App\Models;

use GuzzleHttp\Client;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Zijinghua\Zvoyager\App\Resources\UserResource;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    JWTSubject
{
    use Authenticatable, Authorizable, CanResetPassword, Notifiable;

    protected $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client(['http_errors' => false]);
    }

    /**
     * 搜索用户
     * @param $params   关键字包括username,mobile,email,wechat_id,account
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function search($params)
    {
        $searchUri = config('zvoyager.usercenter.host') . config('zvoyager.usercenter.api.search_uri');

        $response = $this->client->request('get', $searchUri, [
            'query' => $params
        ]);
        $result = json_decode($response->getBody()->__toString(),true);
        $code = $response->getStatusCode();
        if ($code != 200) {
            return $result;
        }
        $user = $this->firstOrNew($result['data'][0]);
        return $user;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'usr' => new UserResource($this),
        ];
    }
}
