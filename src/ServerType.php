<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

namespace Ody\Server;

class ServerType
{
    public const HTTP_SERVER = \Swoole\Http\Server::class;

    public const WS_SERVER = \Swoole\WebSocket\Server::class;

    public const TCP_SERVER = \Swoole\Server::class;
}