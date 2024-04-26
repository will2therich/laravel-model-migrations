<?php

namespace will2therich\LaravelModelMigrations\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use will2therich\LaravelModelMigrations\Commands\MigrateCommand;
use will2therich\LaravelModelMigrations\Commands\ModelCommand;
use will2therich\LaravelModelMigrations\Commands\ResourceCommand;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateCommand::class,
                ModelCommand::class,
                ResourceCommand::class,
            ]);
        }
    }
}
