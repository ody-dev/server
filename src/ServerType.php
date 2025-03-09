<?php

namespace Ody\Server;

class ServerType
{
    public const HTTP_SERVER = \Swoole\Http\Server::class;

    public const WS_SERVER = \Swoole\WebSocket\Server::class;

    public const TCP_SERVER = \Swoole\Server::class;
}