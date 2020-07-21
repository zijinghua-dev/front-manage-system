<?php
namespace Zijinghua\Zvoyager;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use TCG\Voyager\Http\Middleware\VoyagerAdminMiddleware;
use Zijinghua\Zbasement\Http\Models\Contracts\UserModelInterface;
use Zijinghua\Zbasement\Providers\BaseServiceProvider;
use Zijinghua\Zvoyager\Http\Middlewares\CheckExternalNames;
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
use Zijinghua\Zvoyager\Providers\RouteServiceProvider;

class ZServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
            $this->registerPublishableResources();
        }
        $this->registerService();
        $this->registerProvider();
    }

    private function registerProvider(){
        $this->app->register(RouteServiceProvider::class);
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
    public function boot(Router $router, Dispatcher $event)
    {
        $this->registerConfig();
        \Auth::extend('zguard', function(){
            return app(ZGuard::class);   //返回自定义 guard 实例
        });
        \Auth::provider('zuserprovider', function () {
            return new ClientRestfulUserProvider();
        });
        $router->aliasMiddleware('checkExternalNames', CheckExternalNames::class);
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
                $this->getPublishablePath()."/configs/zvoyager.php" => config_path('zvoyager.php'),
            ],
        ];

        foreach ($publishable as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom( $this->getPublishablePath(). '/configs/code/auth.php', 'zbasement.code.auth');
        $this->mergeConfigFrom( $this->getPublishablePath(). '/configs/code/user.php', 'zbasement.code.user');
//        $this->mergeConfigFrom( $this->getPublishablePath().'/configs/fields.php', 'zbasement.fields');
        $this->mergeConfigFrom( $this->getPublishablePath().'/configs/validation/rules/auth.php', 'zbasement.validation.rules.auth');
        $this->mergeConfigFrom( $this->getPublishablePath().'/configs/validation/rules/user.php', 'zbasement.validation.rules.user');
        $this->mergeConfigFrom( $this->getPublishablePath().'/configs/validation/messages/auth.php', 'zbasement.validation.messages.auth');
        $this->mergeConfigFrom( $this->getPublishablePath().'/configs/validation/messages/user.php', 'zbasement.validation.messages.user');
    }
}