<?php

namespace Ody\Server;

use Ody\Swoole\HotReload\Watcher;
use Swoole\Http\Server as HttpServer;
use Swoole\Process;
use Swoole\Websocket\Server as WsServer;

class ServerManager
{
    public HttpServer|WsServer $server;

    /**
     * @var string
     */
    protected static string $serverType;

    protected static $serverState;

    public static function init(string $serverType): static
    {
        static::$serverType = $serverType;

        return new static();
    }

    public function start(): void
    {
        $this->server->start();
    }

    public function createServer(?array $config): static
    {
        $this->server = new static::$serverType(
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 9501,
            $this->getSslConfig(
                $config['mode'] ?? SWOOLE_BASE,
                $config['ssl'] ?? []
            ),
            $config['sock_type'] ?? SWOOLE_SOCK_TCP,
        );

        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setServerConfig(array $config): static
    {
        $this->server->set($config);

        return $this;
    }

    /**
     * Get an instance of the initialized server
     *
     * @return HttpServer|WsServer
     */
    public function getServerInstance(): HttpServer|WsServer
    {
        return $this->server;
    }

    /**
     * Register the server callback methods
     *
     * @param array $callbacks
     * @return $this
     */
    public function registerCallbacks(array $callbacks): static
    {
        array_walk($callbacks,
            fn (&$callback, $event) => $this->server->on($event, [...$callback])
        );

        return $this;
    }

    /**
     * Get the SSL config
     * TODO: work this out better
     *
     * @param $serverMode
     * @param array $config
     * @return int
     */
    private function getSslConfig($serverMode, array $config): int
    {
        if (
            !is_null($config['ssl_cert_file']) &&
            !is_null($config['ssl_key_file'])
        ) {
            return SWOOLE_SSL;
        }

        return $serverMode;
    }

    /**
     * Enables a watcher for hot reloading
     * specified files and folders
     *
     * @param int $enableWatcher
     * @param object $serverState
     * @return ServerManager
     */
    public function setWatcher(int $enableWatcher, array $paths, object $serverState): static
    {
        if ($enableWatcher) {
            (new Process(function (Process $process) use ($paths, $serverState) {
                $serverState::getInstance()
                    ->setWatcherProcessId($process->pid);
                (new Watcher($paths))->start();
            }))->start();

//            logger()->info("Watcher started");
        }

        return $this;
    }

    public function daemonize(bool $daemonize): static
    {
        $this->server->set([
            'daemonize' => $daemonize,
        ]);

        return $this;
    }
}