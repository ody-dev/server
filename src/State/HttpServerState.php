<?php

namespace Ody\Server\State;

class HttpServerState extends ServerState
{
    protected static ?self $instance = null;

    protected string $serverType = 'httpServer';

    public static function getInstance(): self
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        return self::$instance = new self();
    }

    public function httpServerIsRunning(): bool
    {
        $managerProcessId = $this->getManagerProcessId();
        $masterProcessId = $this->getMasterProcessId();
        if (
            !is_null($managerProcessId) &&
            !is_null($masterProcessId)
        ){
            return (
                posix_kill($managerProcessId, SIG_DFL) &&
                posix_kill($masterProcessId, SIG_DFL)
            );
        }

        return false;
    }
}