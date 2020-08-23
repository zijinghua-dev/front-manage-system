<?php
namespace Zijinghua\Zvoyager;


use Zijinghua\Zvoyager\Http\Contracts\ActionServiceInterface;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeServiceInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupFamilyModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupParentModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupRepositoryInterface;
//use Zijinghua\Zvoyager\Http\Middlewares\CheckExternalNames;
use Zijinghua\Zvoyager\Http\Contracts\GuopModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GurModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\ObjectActionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\ParameterServiceInterface;
use Zijinghua\Zvoyager\Http\Contracts\RoleModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\RoleServiceInterface;
use Zijinghua\Zvoyager\Http\Middlewares\CheckExternalNames;
use Zijinghua\Zvoyager\Http\Middlewares\CheckGroup;
use Zijinghua\Zvoyager\Http\Middlewares\CheckParent;
use Zijinghua\Zvoyager\Http\Middlewares\SetUserId;
use Zijinghua\Zvoyager\Http\Middlewares\Uuid;

use Zijinghua\Zvoyager\Http\Models\GroupFamily;
use Zijinghua\Zvoyager\Http\Models\GroupObject;
use Zijinghua\Zvoyager\Http\Models\GroupParent;
use Zijinghua\Zvoyager\Http\Models\GroupUserRole;
use Zijinghua\Zvoyager\Http\Models\ObjectAction;
use Zijinghua\Zvoyager\Http\Models\GroupRolePermission;
use Zijinghua\Zvoyager\Http\Models\GroupUserObjectPermission;
use Zijinghua\Zvoyager\Http\Models\Role;
use Zijinghua\Zvoyager\Http\Repositories\ActionRepository;
use Zijinghua\Zbasement\Http\Repositories\Contracts\UserRepositoryInterface;
use Zijinghua\Zvoyager\Http\Contracts\ActionRepositoryInterface;
use Zijinghua\Zvoyager\Http\Repositories\DatatypeRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Zijinghua\Zbasement\Http\Models\Contracts\UserModelInterface;
use Zijinghua\Zbasement\Providers\BaseServiceProvider;
use Zijinghua\Zvoyager\Http\Contracts\ActionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\AuthorizeServiceInterface;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\DatatypeRepositoryInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupDatatypeModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupUserPermissionModelInterface;
use Zijinghua\Zvoyager\Http\Contracts\GroupServiceInterface;
use Zijinghua\Zvoyager\Http\Middlewares\Authorize;
use Zijinghua\Zvoyager\Http\Middlewares\SetRequestParameters;
use Zijinghua\Zvoyager\Http\Models\Action;
use Zijinghua\Zvoyager\Http\Models\Datatype;
use Zijinghua\Zvoyager\Http\Models\Group;
use Zijinghua\Zvoyager\Http\Models\GroupDatatype;
use Zijinghua\Zvoyager\Http\Models\GroupUserPermission;
use Zijinghua\Zvoyager\Http\Repositories\GroupRepository;
use Zijinghua\Zvoyager\Http\Resources\UserResource;
use Zijinghua\Zvoyager\Http\Contracts\AuthServiceInterface;

use Zijinghua\Zbasement\Http\Models\RestfulUser;
use Zijinghua\Zbasement\Http\Repositories\RestfulUserRepository;
use Zijinghua\Zvoyager\Http\Contracts\UserServiceInterface;
use Zijinghua\Zvoyager\Http\Services\ActionService;
use Zijinghua\Zvoyager\Http\Services\AuthorizeService;
use Zijinghua\Zvoyager\Http\Services\AuthService;
use Illuminate\Foundation\AliasLoader;
use Zijinghua\Zvoyager\Guards\ZGuard;

use Zijinghua\Zvoyager\Http\Services\DatatypeService;
use Zijinghua\Zvoyager\Http\Services\GroupService;
use Zijinghua\Zvoyager\Http\Services\ParameterService;
use Zijinghua\Zvoyager\Http\Services\RoleService;
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
        $this->app->bind('userModel', function () {
            return new RestfulUser();
        });


        $loader->alias('userRepository', UserRepositoryInterface::class);
        $this->app->bind('userRepository', function () {
            return new RestfulUserRepository();
        });

        $loader->alias('groupModel', GroupModelInterface::class);
        $this->app->bind('groupModel', function () {
            return new Group();
        });

        $loader->alias('groupRepository', GroupRepositoryInterface::class);
        $this->app->bind('groupRepository', function () {
            return new GroupRepository();
        });

        $loader->alias('groupService', GroupServiceInterface::class);
        $this->app->singleton('groupService', function () {
            return new GroupService();
        });

        $loader->alias('datatypeModel', DatatypeModelInterface::class);
        $this->app->bind('datatypeModel', function () {
            return new Datatype();
        });

        $loader->alias('datatypeRepository', DatatypeRepositoryInterface::class);
        $this->app->bind('datatypeRepository', function () {
            return new DatatypeRepository();
        });


        $loader->alias('groupDatatypeModel', GroupDatatypeModelInterface::class);
        $this->app->bind('groupDatatypeModel', function () {
            return new GroupDatatype();
        });


        $loader->alias('actionModel', ActionModelInterface::class);
        $this->app->bind('actionModel', function () {
            return new Action();
        });

        $loader->alias('actionRepository', ActionRepositoryInterface::class);
        $this->app->bind('actionRepository', function () {
            return new ActionRepository();
        });


        $loader->alias('authorizeService', AuthorizeServiceInterface::class);
        $this->app->singleton('authorizeService', AuthorizeService::class);

        $loader->alias('groupUserPermissionModel', GroupUserPermissionModelInterface::class);
        $this->app->bind('groupUserPermissionModel', function () {
            return new GroupUserPermission();
        });

        $loader->alias('groupRolePermissionModel', GroupRolePermissionModelInterface::class);
        $this->app->bind('groupRolePermissionModel', function () {
            return new GroupRolePermission();
        });

        $loader->alias('objectActionModel', ObjectActionModelInterface::class);
        $this->app->bind('objectActionModel', function () {
            return new ObjectAction();
        });

        $loader->alias('groupObjectModel', GroupObjectModelInterface::class);
        $this->app->bind('groupObjectModel', function () {
            return new GroupObject();
        });

        $loader->alias('parameterService', ParameterServiceInterface::class);
        $this->app->singleton('parameterService', function () {
            return new ParameterService();
        });

        $loader->alias('guopModel', GuopModelInterface::class);
        $this->app->bind('guopModel', function () {
            return new GroupUserObjectPermission();
        });

        $loader->alias('groupParentModel', GroupParentModelInterface::class);
        $this->app->bind('groupParentModel', function () {
            return new GroupParent();
        });

        $loader->alias('groupUserRoleModel', GurModelInterface::class);
        $this->app->bind('groupUserRoleModel', function () {
            return new GroupUserRole();
        });

        $loader->alias('groupFamilyModel', GroupFamilyModelInterface::class);
        $this->app->bind('groupFamilyModel', function () {
            return new GroupFamily();
        });

        $loader->alias('roleModel', RoleModelInterface::class);
        $this->app->bind('roleModel', function () {
            return new Role();
        });

        $loader->alias('roleService', RoleServiceInterface::class);
        $this->app->bind('roleService', function () {
            return new RoleService();
        });

        $loader->alias('datatypeService', DatatypeServiceInterface::class);
        $this->app->bind('datatypeService', function () {
            return new DatatypeService();
        });

        $loader->alias('actionService', ActionServiceInterface::class);
        $this->app->bind('actionService', function () {
            return new ActionService();
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
        $router->aliasMiddleware('zUserId', SetUserId::class);
//        $router->aliasMiddleware('zUuid', Uuid::class);
//        $router->aliasMiddleware('zCheckParent', CheckParent::class);
        $router->aliasMiddleware('zCheckExternalNames', CheckExternalNames::class);

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