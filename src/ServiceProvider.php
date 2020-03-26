<?php
namespace Zijinghua\FM;

use Illuminate\Support;

class ServiceProvider extends Support\ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
    }

    public function registerConsoleCommands()
    {
        $this->commands(Commands\Install::class);
    }
}