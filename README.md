# User Authentication System
## Install
1.安装命令
>composer require zijinghua/zvoyager

2.如果是第一次安装，或完全卸载后安装，需要执行初始化命令
>php artisan zvoyager:install

3.修改config/auth.php
>修改auth.php中的guard
~~~shell script
        'api' => [
            'driver' => 'zguard',
            'provider' => 'zusers',
            'hash' => false,
        ],    
~~~
>修改auth.php中的provider
~~~shell script
        'zusers' => [
            'driver' => 'zuserprovider'
        ],
~~~
4.修改AuthController控制器
>增加获取凭证方法
~~~php
    /**
     * 获取凭证
     * @param $request
     * @return bool
     */
    protected function setCredentials($request)
    {
        collect(config('zvoyager.usercenter.fields'))->contains(function ($value) use ($request) {
            if ($account = $request->get($value)) {
                $this->account = $value;
                $this->username();
                $password = $request->get('password');
                $this->credentials = [$value => $account, 'password' => $password];
            }
        });
    }
#修改login方法中，获取凭证的逻辑
~~~
>修改login方法
~~~php
    /**
     * Get a JWT via given credentials.
     *
     * @param LoginRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        //现有获取凭证的逻辑
        //$credentials = $request->all(['username', 'password']);
        //修改后的获取凭证逻辑 
        $this->setCredentials($request);
        $credentials = $this->credentials;
        
        /* @var $guard \Tymon\JWTAuth\JWTGuard */
        $guard = auth('api');

        event(new Api\RetrieveTokenAttemptingEvent($credentials));
        // 获取登陆结果
        $result = $guard->attempt($credentials);
        // 如果返回结果不是token，则返回用户中心的错误提示信息
        if (is_string($result)) {
            event(new Api\TokenGeneratedEvent($guard));
            return $this->respondWithToken($result);
        } else {
            event(new Api\RetrieveTokenFailureEvent($credentials));
            return $this->toResopnse($result);
        }
    }
~~~
>增加错误输出方法
~~~php
    /**
     * Get the response
     * @param $response
     * @return \Illuminate\Http\JsonResponse
     */
    protected function toResopnse($response)
    {
        return response()->json($response);
    }
~~~



