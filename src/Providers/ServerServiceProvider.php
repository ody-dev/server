<?php

namespace Ody\Server\Providers;

use Ody\Foundation\Providers\ServiceProvider;
use Ody\Server\Commands\ReloadCommand;
use Ody\Server\Commands\StartCommand;
use Ody\Server\Commands\StopCommand;
use Ody\Server\ServerManager;
use Ody\Support\Config;
use Psr\Log\LoggerInterface;

class ServerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register ServerManager as a singleton
        $this->singleton(ServerManager::class, function ($container) {
            $serverManager = new ServerManager();

            // Inject Logger if available
            if ($container->has(LoggerInterface::class)) {
                $serverManager->setLogger($container->make(LoggerInterface::class));
            }

            // Inject Config if available
            if ($container->has(Config::class)) {
                $serverManager->setConfig($container->make(Config::class));
            }

            return $serverManager;
        });
    }

    public function boot(): void
    {
        $this->registerCommands([
            StartCommand::class,
            StopCommand::class,
            ReloadCommand::class,
        ]);
    }
}