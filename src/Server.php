<?php
declare(strict_types=1);

namespace Ody\HttpServer;

use Ody\Core\Console\Style;
use Ody\Core\Foundation\App;
use Ody\Core\Foundation\Http\Request;
use Ody\Swoole\Coroutine\ContextManager;
use Ody\Swoole\HotReload\Watcher;
use Ody\Swoole\ServerState;
use Swoole\Coroutine;
use Swoole\Http\Response;
use Swoole\Http\Server as SwooleServer;
use Swoole\Process;

/**
 * @psalm-api
 */
class Server
{
    /**
     * @var SwooleServer
     */
    private SwooleServer $server;

    /**
     * @var App
     */
    private App $kernel;


    public function __construct() {}

    /**
     * Starts the server
     *
     * @return void
     */
    public function start(): void
    {
        $this->server->start();
    }

    public static function init(): Server
    {
        return new self();
    }

    /**
     * @param App $kernel
     * @param bool $daemonize
     * @return Server
     */
    public function createServer(App $app, bool $daemonize, bool $watcher, Style $io): Server
    {
        $this->kernel = $app;
        $this->server = new SwooleServer(
            config('server.host'),
            (int) config('server.port'),
            $this->getSslConfig(),
            config('server.sock_type')
        );

        if(config('server.runtime.enable_coroutine')) {
            \Swoole\Runtime::enableCoroutine(
                config('server.runtime.hook_flag')
            );
        }

        $this->server->set([
            ...config('server.additional'),
            'daemonize' => (int) $daemonize,
            'enable_coroutine' => false // must be set on false for Runtime::enableCoroutine
        ]);

        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('start', function (SwooleServer $server) use ($io, $watcher) {
            $protocol = ($server->ssl) ? "https" : "http";
            $io->success('http server started successfully');
            $io->info("listen on " . $protocol . "://" . $server->host . ':' . $server->port);
            $io->info('press Ctrl+C to stop the server');

            if ($watcher) {
                (new Process(function (Process $process) {
                    ServerState::getInstance()
                        ->setWatcherProcessId($process->pid);
                    (new Watcher())->start();
                }))->start();

                $io->info('File watcher is enabled');
            }
        });
        $this->server->on('WorkerError', function (SwooleServer $serv, \Swoole\Server\StatusInfo $info) {
            dd($info);
        });

        return $this;
    }

    /**
     * @param \Swoole\Http\Request $request
     * @param Response $response
     * @return void
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response): void
    {
        Coroutine::create(function() use ($request, $response) {
            // Set global variables in the ContextManager
            $this->setContext($request);

            // Create the app and handle the request
            (new RequestCallback($this->kernel))
                ->handle($request, $response);
        });
    }

    /**
     * @param SwooleServer $server
     * @param int $workerId
     * @return void
     */
    public function onWorkerStart(SwooleServer $server, int $workerId): void
    {
        if ($workerId == config('server.additional.worker_num') - 1){
            $this->saveWorkerIds($server);
        }
    }

    /**
     * @param SwooleServer $server
     * @return void
     */
    protected function saveWorkerIds(SwooleServer $server): void
    {
        $workerIds = [];
        for ($i = 0; $i < config('server.additional.worker_num'); $i++){
            $workerIds[$i] = $server->getWorkerPid($i);
        }

        $serveState = ServerState::getInstance();
        $serveState->setMasterProcessId($server->getMasterPid());
        $serveState->setManagerProcessId($server->getManagerPid());
        $serveState->setWorkerProcessIds($workerIds);
    }

    /**
     * @param Request $request
     * @return void
     */
    private function setContext(\Swoole\Http\Request $request): void
    {
        ContextManager::set('_GET', (array)$request->get);
        ContextManager::set('_GET', (array)$request->get);
        ContextManager::set('_POST', (array)$request->post);
        ContextManager::set('_FILES', (array)$request->files);
        ContextManager::set('_COOKIE', (array)$request->cookie);
        ContextManager::set('_SERVER', (array)$request->server);
        ContextManager::set('request', Request::getInstance());
    }

    /**
     * @return int
     */
    private function getSslConfig(): int
    {
        if (
            !is_null(config('server.ssl.ssl_cert_file')) &&
            !is_null(config('server.ssl.ssl_key_file'))
        ) {
            return config('server.mode', SWOOLE_SSL) | SWOOLE_SSL;
        }

        return config('server.mode');
    }
}
