<?php
/*
 *  This file is part of ODY framework.
 *
 *  @link     https://ody.dev
 *  @document https://ody.dev/docs
 *  @license  https://github.com/ody-dev/ody-foundation/blob/master/LICENSE
 */

/*
 * This file is part of ODY framework
 *
 * @link https://ody.dev
 * @documentation https://ody.dev/docs
 * @license https://github.com/ody-dev/ody-core/blob/master/LICENSE
 */


require_once __DIR__ . '/vendor/autoload.php';

use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;


$server = new \Swoole\Http\Server("0.0.0.0", 9501);
$server->set([
    'worker_num' => 4,
    'admin_server' => '127.0.0.1:9506',
    'enable_coroutine' => true,
]);

$accepted_process_types = SWOOLE_SERVER_COMMAND_MASTER |
    SWOOLE_SERVER_COMMAND_MANAGER |
    SWOOLE_SERVER_COMMAND_EVENT_WORKER |
    SWOOLE_SERVER_COMMAND_TASK_WORKER;

\Ody\Admin::init($server);

$server->on('request', function ($request, $response) {
    error_log('hello');
});

$server->on('start', function (\Swoole\Http\Server $server) {

    \Ody\Admin::start($server);
});

$server->on('afterReload', function (\Swoole\Http\Server $server) {
    \Ody\Admin::init($server);
    \Ody\Admin::start($server);
});

$server->on('beforeShutDown', function (\Swoole\Http\Server $server) {
    if (isset($server->admin_server)) { // @phpstan-ignore isset.property
        error_log('shutdown admin server');
        $server->admin_server->shutdown();
        $server->admin_server = null; // @phpstan-ignore assign.propertyType
    }
});

$server->start();