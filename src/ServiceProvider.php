<?php
namespace Zijinghua\Zvoyager;

use Illuminate\Support;

class ServiceProvider extends Support\ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishResources();
        }

        $this->mergeConfig();
    }

    public function registerConsoleCommands()
    {
        $this->commands(Commands\Install::class);
    }

    protected function getPublishablePath()
    {
        return realpath(__DIR__.'/../publishable');
    }

    protected function publishResources()
    {
        $publishable = [
            'config' => [
                $this->getPublishablePath()."/config/zvoyager.php" => config_path('voyager.php'),
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