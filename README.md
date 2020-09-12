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
~~~
5.修改auth中间件
>修改App\Http下的Kernel.php文件，更换auth中间件，当token生存周期结束时，刷新token
~~~php
// 'auth' => \App\Http\Middleware\Authenticate::class,修改如下
'auth' => \Zijinghua\Zvoyager\Http\Middlewares\Authenticate::class,
~~~



