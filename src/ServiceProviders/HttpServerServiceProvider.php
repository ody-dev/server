<?php

namespace Ody\HttpServer\ServiceProviders;

use Ody\Core\Kernel;
use Ody\Core\ServiceProvider\Resolver;
use Ody\Core\ServiceProvider\ServiceProvider;
use Ody\HttpServer\Commands\ReloadCommand;
use Ody\HttpServer\Commands\StartCommand;
use Ody\HttpServer\Commands\StopCommand;

class HttpServerServiceProvider
{
    public function commands(): array
    {
        return [
            StartCommand::class,
            StopCommand::class,
            ReloadCommand::class,
        ];
    }
}