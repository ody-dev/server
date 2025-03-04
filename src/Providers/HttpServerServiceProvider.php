<?php

namespace Ody\HttpServer\Providers;

use Ody\Core\Foundation\Providers\ServiceProvider;
use Ody\HttpServer\Commands\ReloadCommand;
use Ody\HttpServer\Commands\StartCommand;
use Ody\HttpServer\Commands\StopCommand;

class HttpServerServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands = [
                StartCommand::class,
                StopCommand::class,
                ReloadCommand::class,
            ];
        }
    }
}