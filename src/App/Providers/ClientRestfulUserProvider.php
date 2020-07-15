<?php

namespace Zijinghua\Zvoyager\App\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Auth\UserProvider;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Models\User;

class ClientRestfulUserProvider implements UserProvider
{
    protected $client;

    public function createRestfulClient(){
        if(!$this->client){
            $this->client = new Client(['http_errors' => false]);
        }
    }
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        //guzzle客户端
        $this->createRestfulClient();
        $response = $this->client->get('/api/user', [
            'query' => [		//get查询字符串参数组
                'id' => $identifier,
            ],
        ]);

        $json=json_decode($response->getBody());
        //判断错误、异常
        $user=new User(json_decode(json_encode($json->user),true));
        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        return;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\Illuminate\Database\Eloquent\Model  $user
     * @param  string  $token
     * @return void
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        return;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        //只能是'email','weixin_uniqueid','email','mobile','account'五种单一输入
        if (empty($credentials) ||
            (count($credentials) === 1 &&
                Str::contains($this->firstCredentialKey($credentials), 'password'))) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $this->createRestfulClient();

        $query=[];
        foreach ($credentials as $key => $value) {
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
            $message = config('zvoyager.auth.message.user_has_not_exists');
            throw new Exception($message, $code);
        }
        $user = app(User::class)->firstOrNew($result['data'][0]);
        return $user;
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

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials( $user, array $credentials)
    {
        $this->createRestfulClient();
        $loginUri = config('zvoyager.usercenter.host') . config('zvoyager.usercenter.api.login_uri');
        $requestData = [
            'form_params' => $credentials
        ];

        $response = $this->client->request('post', $loginUri, $requestData);
        $result = json_decode($response->getBody()->__toString(),true);
        $code = $response->getStatusCode();
        if ($code != 200) {
            throw new Exception($result['message'], $code);
        }
        return $result;
    }
}
