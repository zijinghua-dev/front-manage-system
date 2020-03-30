<?php

namespace Zijinghua\Zvoyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Zijinghua\Zvoyager\Base;
use Zijinghua\Zvoyager\ServiceProvider;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zvoyager:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this package init';

    protected $filesystem;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    protected function publishFiles()
    {
        if (isset(ServiceProvider::$publishes[ServiceProvider::class])) {
            $this->info('Publish resource to local');
            $this->call('vendor:publish', ['--provider' => ServiceProvider::class, '--tag' => ['config']]);
        }
    }

    protected function publishRoutes()
    {
        $contents = $this->filesystem->get(base_path('routes/api.php'));
        if (false === strpos($contents, 'Base::snackRoute()')) {
            $this->info('Publish snack.php route to routes/api.php');

            $pageApiVersion = \Zijinghua\Zvoyager\Base::getPageApiVersion();
            $this->filesystem->append(
                base_path('routes/api.php'),
                "\n\nRoute::group(['middleware' => 'api', 'as' => 'zvoyager.', 'prefix' => '".
                strtolower($pageApiVersion)."/zvoyager'], function() {\n".
                "\tZijinghua\Zvoyager\Base::snackRoute();\n});\n"
            );
        }
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->publishFiles();
        $this->publishRoutes();
    }
}
