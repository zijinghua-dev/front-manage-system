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
         'Zijinghua\Zbasement\Http\Repositories\RestfulUserRepository' => 'Zijinghua\Zvoyager\Policies\BasePolicy',
    ];

    private function getSlugFromAlias($alias,$classType){

    }
    private function getSlugFromClass($class){
        $basename=basename($class);
        //去掉model，repository，service
    }
    public function loadAuth()
    {
        // 基本安全策略绑定所有在系统内注入repository
//        $bindings=app();


            $dataTypes=Zsystem::typeSearch('repository');
                foreach ($dataTypes as $key=>$dataType) {
                    $object=resolve($key);
                    $bindingClass=get_class($object);
                    //凡是没有直接绑定的，都用basepolicy
                    if(!isset($this->policies[$bindingClass])){
                        $this->policies[$bindingClass] = BasePolicy::class;
                    }
                }

                $this->registerPolicies();
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
