<?php
declare(strict_types=1);

namespace Ody\Server;

class ServerEvent
{
    /**
     * Server onStart event.
     */
    public const ON_START = 'start';

    /**
     * Server onWorkerStart event.
     */
    public const ON_WORKER_START = 'workerStart';

    /**
     * Server onWorkerStop event.
     */
    public const ON_WORKER_STOP = 'workerStop';

    /**
     * Server onWorkerExit event.
     */
    public const ON_WORKER_EXIT = 'workerExit';

    /**
     * Server onWorkerError event.
     */
    public const ON_WORKER_ERROR = 'workerError';

    /**
     * Server onPipeMessage event.
     */
    public const ON_PIPE_MESSAGE = 'pipeMessage';

    /**
     * Server onRequest event.
     */
    public const ON_REQUEST = 'request';

    /**
     * Server onReceive event.
     */
    public const ON_RECEIVE = 'receive';

    /**
     * Server onConnect event.
     */
    public const ON_CONNECT = 'connect';

    /**
     * Server onDisconnectevent.
     */
    public const ON_DISCONNECT = 'disconnect';

    /**
     * Server onOpen event.
     */
    public const ON_OPEN = 'open';

    /**
     * Server onMessage event.
     */
    public const ON_MESSAGE = 'message';

    /**
     * Server onClose event.
     */
    public const ON_CLOSE = 'close';

    /**
     * Server onTask event.
     */
    public const ON_TASK = 'task';

    /**
     * Server onFinish event.
     */
    public const ON_FINISH = 'finish';

    /**
     * Server onShutdown event.
     */
    public const ON_SHUTDOWN = 'shutdown';

    /**
     * Server onPacket event.
     */
    public const ON_PACKET = 'packet';

    /**
     * Server onManagerStart event.
     */
    public const ON_MANAGER_START = 'managerStart';

    /**
     * Server onManagerStop event.
     */
    public const ON_MANAGER_STOP = 'managerStop';

    /**
     * Before server start, it's not a swoole event.
     */
    public const ON_BEFORE_START = 'beforeStart';

    public static function isSwooleEvent($event): bool
    {
        if ($event == self::ON_BEFORE_START) {
            return false;
        }
        return true;
    }
}