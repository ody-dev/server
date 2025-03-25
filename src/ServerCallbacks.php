<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

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
        $protocol = ($server->ssl) ? "https" : "http";
        logger()->info("Server started successfully");
        logger()->info("Listening on " . $protocol . "://" . $server->host . ':' . $server->port);
        logger()->info("Press Ctrl+C to stop the server");

        // TODO: Implement admin API server
//        AdminServer::start($server);
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
        logger()->debug("Manager started successfully");
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
        logger()->debug("Worker error: $workerId - pid: $workerPid, exit_code: $exitCode");
    }
}
