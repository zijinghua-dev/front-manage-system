<?php

namespace Zijinghua\Zvoyager\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Facades\Voyager as VoyagerFacade;
use TCG\Voyager\Policies\BasePolicy;
use Zijinghua\Zbasement\Facades\Zsystem;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
//         'Zijinghua\Zbasement\Http\Repositories\RestfulUserRepository' => 'Zijinghua\Zvoyager\Policies\BasePolicy',
    ];

//    private function getSlugFromAlias($alias,$classType){
//
//    }
//    private function getSlugFromClass($class){
//        $basename=basename($class);
//        //去掉model，repository，service
//    }
    public function loadAuth()
    {
        // 基本安全策略:绑定所有slug+action
//        $bindings=app();
        $repository=Zsystem::repository('dataType');
        $slugs=$repository->all('slug')->pluck('slug')->toArray();
        $repository=Zsystem::repository('action');
        $actions=$repository->all('name')->pluck('name')->toArray();
        foreach ($slugs as $key=>$slug) {
            foreach ($actions as $key=>$action){
                $ability=$slug.'_'.$action;
                if(!Gate::has($ability)){
                    Gate::define($ability,'Zijinghua\Zvoyager\Policies\BasePolicy@checkPermission');
                }
            }
        }
//                    $object=resolve($key);
//                    $bindingClass=get_class($object);
//                    //凡是没有直接绑定的，都用basepolicy
//                    if(!isset($this->policies[$bindingClass])){
//                        $this->policies[$bindingClass] = BasePolicy::class;
//                    }
//                }
//
//                $this->registerPolicies();
    }
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadAuth();

        //
    }
}
