<?php
namespace Zijinghua\Zvoyager;

use Zijinghua\Zvoyager\App\Guards\ZGuard;
use Zijinghua\Zvoyager\App\Providers\ClientRestfulUserProvider;
use Illuminate\Support\ServiceProvider;

class ZServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
            $this->registerPublishableResources();
        }
    }

    public function boot()
    {
        $this->mergeConfig();
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

    protected function mergeConfig()
    {
        $this->mergeConfigFrom($this->getPublishablePath()."/config/zvoyager.php", 'zvoyager');
    }
}