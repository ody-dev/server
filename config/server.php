<?php
/*
 * This file is part of ODY framework
 *
 * @link https://ody.dev
 * @documentation https://ody.dev/docs
 * @license https://github.com/ody-dev/ody-core/blob/master/LICENSE
 */

use Ody\Server\ServerEvent;

return [
    'mode' => SWOOLE_PROCESS,
    'host' => env('HTTP_SERVER_HOST', '127.0.0.1'),
    'port' => env('HTTP_SERVER_PORT', 9501),
    'sock_type' => SWOOLE_SOCK_TCP,
    'additional' => [
        'daemonize' => false,
        'worker_num' => env('HTTP_SERVER_WORKER_COUNT', swoole_cpu_num() * 2),
        'dispatch_mode' => 3, // Important: This ensures connections stay with their worker, does not work in SWOOLE_BASE
        'open_http_protocol' => true,
        /**
         * log level
         * SWOOLE_LOG_DEBUG (default)
         * SWOOLE_LOG_TRACE
         * SWOOLE_LOG_INFO
         * SWOOLE_LOG_NOTICE
         * SWOOLE_LOG_WARNING
         * SWOOLE_LOG_ERROR
         */
        'log_level' => 1,
        'log_file' => base_path('storage/logs/ody_server.log'),
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
        'log_date_format' => '%Y-%m-%d %H:%M:%S',

        // Coroutine
        'enable_coroutine' => false,
        'max_coroutine' => 3000,
        'send_yield' => false,

        'ssl_cert_file' => null,
        'ssl_key_file' => null,
    ],

    'runtime' => [
        /**
         * enabling this will run clients like MySQL and Redis in a non-blocking fashion
         * https://wiki.swoole.com/en/#/runtime
         */
        'enable_coroutine' => true,
        /**
         * SWOOLE_HOOK_TCP - Enable TCP hook only
         * SWOOLE_HOOK_TCP | SWOOLE_HOOK_UDP | SWOOLE_HOOK_SOCKETS - Enable TCP, UDP and socket hooks
         * SWOOLE_HOOK_ALL - Enable all runtime hooks
         * SWOOLE_HOOK_ALL ^ SWOOLE_HOOK_FILE ^ SWOOLE_HOOK_STDIO - Enable all runtime hooks except file and stdio hooks
         * 0 - Disable runtime hooks
         */
        'hook_flag' => SWOOLE_HOOK_ALL,
    ],

    /**
     * Override default callbacks for server events
     */
    'callbacks' => [
        ServerEvent::ON_REQUEST => [\Ody\Foundation\HttpServer::class, 'onRequest'],
        ServerEvent::ON_START => [\Ody\Server\ServerCallbacks::class, 'onStart'],
        ServerEvent::ON_WORKER_ERROR => [\Ody\Server\ServerCallbacks::class, 'onWorkerError'],
        ServerEvent::ON_WORKER_START => [\Ody\Server\ServerCallbacks::class, 'onWorkerStart'],
    ],

    /**
     * Configure what directories or files must be
     * watched for hot reloading.
     */
    'watch' => [
        'app',
        'config',
        'database',
        'composer.lock',
        '.env',
    ]
];
