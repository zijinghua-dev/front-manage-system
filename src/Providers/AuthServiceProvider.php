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

    ];

    public function loadAuth()
    {

    }
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadAuth();
    }
}
