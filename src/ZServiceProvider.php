<?php
namespace Zijinghua\Zvoyager;

use Zijinghua\Zbasement\Http\Models\Contracts\UserModelInterface;
use Zijinghua\Zbasement\Providers\BaseServiceProvider;
use Zijinghua\Zvoyager\Http\Resources\UserResource;
use Zijinghua\Zvoyager\Http\Contracts\AuthServiceInterface;
use Zijinghua\Zbasement\Http\Contracts\UserRepositoryInterface;
use Zijinghua\Zbasement\Http\Models\RestfulUser;
use Zijinghua\Zbasement\Http\Repositories\RestfulUserRepository;
use Zijinghua\Zvoyager\Http\Contracts\UserServiceInterface;
use Zijinghua\Zvoyager\Http\Services\AuthService;
use Illuminate\Foundation\AliasLoader;
use Zijinghua\Zvoyager\Guards\ZGuard;
use Zijinghua\Zvoyager\Http\Services\UserService;
use Zijinghua\Zvoyager\Providers\ClientRestfulUserProvider;

class ZServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
            $this->registerPublishableResources();
        }
        $this->registerService();
    }

    protected function registerService(){
        $loader = AliasLoader::getInstance();

        $loader->alias('userResource', UserResource::class);

        $loader->alias('authService', AuthServiceInterface::class);
        $this->app->singleton('authService', function () {
            return new AuthService();
        });
        $loader->alias('userService', UserServiceInterface::class);
        $this->app->singleton('userService', function () {
            return new UserService();
        });

        $loader->alias('userModel', UserModelInterface::class);
        $this->app->singleton('userModel', function () {
            return new RestfulUser();
        });


        $loader->alias('userRepository', UserRepositoryInterface::class);
        $this->app->singleton('userRepository', function () {
            return new RestfulUserRepository();
        });

    }
    public function boot()
    {
        $this->registerConfig();
        \Auth::extend('zguard', function(){
            return app(ZGuard::class);   //返回自定义 guard 实例
        });
        \Auth::provider('zuserprovider', function () {
            return new ClientRestfulUserProvider();
        });
    }

    public function registerConsoleCommands()
    {
        $this->commands(Commands\Install::class);
    }

    protected function getPublishablePath()
    {
        return realpath(__DIR__.'/../publishable');
    }

    protected function registerPublishableResources()
    {
        $publishable = [
            'config' => [
                $this->getPublishablePath()."/config/zvoyager.php" => config_path('zvoyager.php'),
            ],
        ];

        foreach ($publishable as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }

    protected function registerConfig()
    {
//        $this->mergeConfigFrom($this->getPublishablePath()."/config/zvoyager.php", 'zvoyager');
        //storerequest和loginrequest
    }
}