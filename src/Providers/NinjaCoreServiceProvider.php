<?php

namespace Ninjacode\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Ninjacode\Core\Commands\LivewireMakeCommand;
use Ninjacode\Core\Commands\ModuleCommand;

class NinjaCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerProviders();
        $this->registerCommands();

        $this->registerPublishables();

        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/config/modules-livewire.php',
            'modules-livewire'
        );
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleCommand::class,
                LivewireMakeCommand::class,
            ]);
        }
    }

    protected function registerProviders()
    {
        $this->app->register(LivewireComponentServiceProvider::class);
    }

    protected function registerPublishables()
    {
        $this->publishes([
            dirname(__DIR__, 2) . '/config/modules-livewire.php' => base_path('config/modules-livewire.php'),
        ], ['modules-livewire-config']);

        $this->publishes([
            __DIR__ . '/Commands/stubs/' => base_path('stubs/modules-livewire'),
        ], ['modules-livewire-stub']);
    }
}
