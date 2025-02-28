<?php

namespace Ody\HttpServer\ServiceProviders;

use Ody\Core\App;
use Ody\Core\ServiceProviders\ServiceProvider;
use Ody\HttpServer\Commands\ReloadCommand;
use Ody\HttpServer\Commands\StartCommand;
use Ody\HttpServer\Commands\StopCommand;

class HttpServerServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot(): array
    {
        return [
            StartCommand::class,
            StopCommand::class,
            ReloadCommand::class,
        ];
    }
}