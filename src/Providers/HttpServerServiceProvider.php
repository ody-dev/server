<?php

namespace Ody\Server\Providers;

use Ody\Core\Foundation\Providers\ServiceProvider;
use Ody\Server\Commands\ReloadCommand;
use Ody\Server\Commands\StartCommand;
use Ody\Server\Commands\StopCommand;

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