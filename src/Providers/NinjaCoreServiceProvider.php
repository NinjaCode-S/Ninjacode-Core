<?php

namespace Ninjacode\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Ninjacode\Core\Console\Commands\ModuleCommand;

class NinjaCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleCommand::class,
            ]);
        }
    }
}
