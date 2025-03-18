<?php
declare(strict_types=1);

namespace Ody\Server;

use Ody\Logger\StreamLogger;
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
        $logger = new StreamLogger('php://stderr');
        $protocol = ($server->ssl) ? "https" : "http";
        $logger->info("Server started successfully");
        $logger->info("Listening on " . $protocol . "://" . $server->host . ':' . $server->port);
        $logger->info("Press Ctrl+C to stop the server");

//        echo "   \033[1mSUCCESS\033[0m  Server started successfully\n";
//        echo "   \033[1mINFO\033[0m  listen on " . $protocol . "://" . $server->host . ':' . $server->port . PHP_EOL;
//        echo "   \033[1mINFO\033[0m  press Ctrl+C to stop the server\n";
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

        $logger = new StreamLogger('php://stderr');
        $logger->debug("Worker $workerId started successfully");
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
     * @param int $workerPid
     * @param int $exitCode
     * @param int $signal
     * @return void
     */
    public static function onWorkerError(SwServer $server, int $workerId, int $workerPid, int $exitCode, int $signal): void
    {
        $logger = new StreamLogger('php://stderr');
        $logger->debug("Worker error: $workerId - pid: $workerPid, exit_code: $exitCode");
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
