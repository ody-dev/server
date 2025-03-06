<?php

namespace Ody\Server\Tests;

use Swoole\Http\Server;

class ServerEvents
{
    public static function onStart (Server $server): void
    {
        // Give the server a chance to start up and avoid zombies
        sleep(5);
        $server->stop();
        $server->shutdown();
    }
}