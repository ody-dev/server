<?php
declare(strict_types=1);

namespace Ody\Server;

use Ody\Server\State\HttpServerState;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Http\Server as SwServer;

/**
 * @psalm-api
 */
class ServerCallbacks
{
    /**
     * @param SwServer $server
     * @return void
     */
    public static function onStart (SwServer $server): void
    {
        $protocol = ($server->ssl) ? "https" : "http";
        echo "   \033[1mSUCCESS\033[0m  Server started successfully\n";
        echo "   \033[1mINFO\033[0m  listen on " . $protocol . "://" . $server->host . ':' . $server->port . PHP_EOL;
        echo "   \033[1mINFO\033[0m  press Ctrl+C to stop the server\n";
    }

    /**
     * @param SwRequest $request
     * @param SwResponse $response
     * @return void
     */
    public static function onRequest(SwRequest $request, SwResponse $response): void
    {

    }

    /**
     * @param SwServer $server
     * @param int $workerId
     * @return void
     */
    public static function onWorkerStart(SwServer $server, int $workerId): void
    {
        // Save worker ids to serverState.json
        if ($workerId == config('server.additional.worker_num') - 1) {
            $workerIds = [];
            for ($i = 0; $i < config('server.additional.worker_num'); $i++) {
                $workerIds[$i] = $server->getWorkerPid($i);
            }

            $serveState = HttpServerState::getInstance();
            $serveState->setMasterProcessId($server->getMasterPid());
            $serveState->setManagerProcessId($server->getManagerPid());
            $serveState->setWorkerProcessIds($workerIds);
        }
    }

    /**
     * @param SwServer $server
     * @return void
     */
    public static function onManagerStart(SwServer $server)
    {

    }

    /**
     * @param SwServer $server
     * @return void
     */
    public static function onManagerStop(SwServer $server)
    {

    }

    public static function onReceive(SwServer $server, int $fd, int $reactorId, string $data)
    {

    }

    /**
     * @param SwServer $server
     * @param int $workerId
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @return void
     */
    public static function onWorkerError(SwServer $server, int $workerId, int $worker_id, int $worker_pid, int $exit_code): void
    {
        error_log("Worker error: $workerId - pid: $worker_pid, exit_code: $exit_code");
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
