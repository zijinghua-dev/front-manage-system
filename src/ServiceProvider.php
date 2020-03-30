<?php
namespace Zijinghua\Zvoyager;

use Illuminate\Support;

class ServiceProvider extends Support\ServiceProvider
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
    }

    public function registerConsoleCommands()
    {
        $this->commands(Commands\Install::class);
    }

    protected function getPublishablePath()
    {
        return realpath(__DIR__.'/../publishable');
    }

    protected function existSkip(array $paths)
    {
        foreach ($paths as $source=>$targe) {
            if (file_exists($targe)) {
                unset($paths[$source]);
            }
        }

        return $paths;
    }

    protected function registerPublishableResources()
    {
        $publishable = [
            'config' => [
                $this->getPublishablePath()."/config/zvoyager.php" => config_path('zvoyager.php'),
            ],
        ];

        foreach ($publishable as $group => $paths) {
            $paths = $this->existSkip($paths);
            if (!empty($paths)) {
                $this->publishes($this->existSkip($paths), $group);
            }
        }
    }

    protected function mergeConfig()
    {
        $this->mergeConfigFrom($this->getPublishablePath()."/config/zvoyager.php", 'zvoyager');
    }
}