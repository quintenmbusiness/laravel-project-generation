<?php

namespace quintenmbusiness\LaravelProjectGeneration;

use Illuminate\Support\ServiceProvider;
use quintenmbusiness\LaravelProjectGeneration\Console\GenerateProjectCommand;

class LaravelProjectGenerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateProjectCommand::class,
            ]);
        }
    }
}
