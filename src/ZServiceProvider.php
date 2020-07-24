<?php
namespace Zijinghua\Zvoyager;

use Zijinghua\Zvoyager\Http\Contracts\GroupRepositoryInterface;
use Zijinghua\Zvoyager\Http\Middlewares\CheckGroup;
use Zijinghua\Zvoyager\Http\Models\GroupObject;
use Zijinghua\Zvoyager\Http\Repositories\ActionRepository;
use Zijinghua\Zbasement\Http\Repositories\Contracts\UserRepositoryInterface;
use Zijinghua\Zvoyager\Http\Contracts\ActionRepositoryInterface;
use Zijinghua\Zvoyager\Http\Repositories\DataTypeRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Zijinghua\Zbasement\Http\Models\Contracts\UserModelInterface;
use Zijinghua\Zbasement\Providers\BaseServiceProvider;
use Zijinghua\Zvoyager\Http\Contracts\ActionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\AuthorizeServiceInterface;
use Zijinghua\Zvoyager\Http\Contracts\DataTypeModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\DataTypeRepositoryInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupDataTypeModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupPermissionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupServiceInterface;
use Zijinghua\Zvoyager\Http\Middlewares\Authorize;
use Zijinghua\Zvoyager\Http\Middlewares\SetRequestParameters;
use Zijinghua\Zvoyager\Http\Models\Action;
use Zijinghua\Zvoyager\Http\Models\DataType;
use Zijinghua\Zvoyager\Http\Models\Group;
use Zijinghua\Zvoyager\Http\Models\GroupDataType;
use Zijinghua\Zvoyager\Http\Models\GroupPermission;
use Zijinghua\Zvoyager\Http\Repositories\GroupRepository;
use Zijinghua\Zvoyager\Http\Resources\UserResource;
use Zijinghua\Zvoyager\Http\Contracts\AuthServiceInterface;

use Zijinghua\Zbasement\Http\Models\RestfulUser;
use Zijinghua\Zbasement\Http\Repositories\RestfulUserRepository;
use Zijinghua\Zvoyager\Http\Contracts\UserServiceInterface;
use Zijinghua\Zvoyager\Http\Services\AuthorizeService;
use Zijinghua\Zvoyager\Http\Services\AuthService;
use Illuminate\Foundation\AliasLoader;
use Zijinghua\Zvoyager\Guards\ZGuard;
use Zijinghua\Zvoyager\Http\Services\GroupService;
use Zijinghua\Zvoyager\Http\Services\UserService;
use Zijinghua\Zvoyager\Providers\AuthServiceProvider;
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
        $this->app->register(AuthServiceProvider::class);
    }
    protected function registerService(){
        $loader = AliasLoader::getInstance();

        $loader->alias('userResource', UserResource::class);

        $loader->alias('authService', AuthServiceInterface::class);
        $this->app->singleton('authService', AuthService::class);

        $loader->alias('userService', UserServiceInterface::class);
        $this->app->singleton('userService', UserService::class);

        $loader->alias('userModel', UserModelInterface::class);
        $this->app->singleton('userModel', function () {
            return new RestfulUser();
        });


        $loader->alias('userRepository', UserRepositoryInterface::class);
        $this->app->singleton('userRepository', function () {
            return new RestfulUserRepository();
        });

        $loader->alias('groupModel', GroupModelInterface::class);
        $this->app->singleton('groupModel', function () {
            return new Group();
        });

        $loader->alias('groupRepository', GroupRepositoryInterface::class);
        $this->app->singleton('groupRepository', function () {
            return new GroupRepository();
        });

        $loader->alias('groupService', GroupServiceInterface::class);
        $this->app->singleton('groupService', function () {
            return new GroupService();
        });

        $loader->alias('dataTypeModel', DataTypeModelInterface::class);
        $this->app->singleton('dataTypeModel', function () {
            return new DataType();
        });

        $loader->alias('dataTypeRepository', DataTypeRepositoryInterface::class);
        $this->app->singleton('dataTypeRepository', function () {
            return new DataTypeRepository();
        });


        $loader->alias('groupDataTypeModel', GroupDataTypeModelInterface::class);
        $this->app->singleton('groupDataTypeModel', function () {
            return new GroupDataType();
        });


        $loader->alias('actionModel', ActionModelInterface::class);
        $this->app->singleton('actionModel', function () {
            return new Action();
        });

        $loader->alias('actionRepository', ActionRepositoryInterface::class);
        $this->app->singleton('actionRepository', function () {
            return new ActionRepository();
        });


        $loader->alias('authorizeService', AuthorizeServiceInterface::class);
        $this->app->singleton('authorizeService', AuthorizeService::class);

        $loader->alias('groupPermissionModel', GroupPermissionModelInterface::class);
        $this->app->singleton('groupPermissionModel', function () {
            return new GroupPermission();
        });

        $loader->alias('groupObjectModel', GroupObjectModelInterface::class);
        $this->app->singleton('groupObjectModel', function () {
            return new GroupObject();
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
        $this->registerMiddleware($router);
    }

    protected function registerMiddleware(Router $router){
        $router->aliasMiddleware('setRequestParameters', SetRequestParameters::class);
        $router->aliasMiddleware('zAuthorize', Authorize::class);
        $router->aliasMiddleware('zCheckGroup', CheckGroup::class);

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