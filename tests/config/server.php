<?php

use Ody\Server\ServerEvent;

return [
    'mode' => SWOOLE_BASE,
    'host' => '127.0.0.1',
    'port' => 9501 ,
    'sock_type' => SWOOLE_SOCK_TCP,
    'additional' => [
        'worker_num' => swoole_cpu_num() * 2 ,
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
//        'log_file' => base_path('/storage/logs/ody_server.log'),
        'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
        'log_date_format' => '%Y-%m-%d %H:%M:%S',

        // Coroutine
        'max_coroutine' => 3000,
        'send_yield' => false,
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
        ServerEvent::ON_REQUEST => [\Ody\Core\Foundation\Http\Server::class, 'onRequest'],
        ServerEvent::ON_START => [\Ody\Server\Tests\ServerEvents::class, 'onStart'],
        ServerEvent::ON_WORKER_ERROR => [\Ody\Server\ServerCallbacks::class, 'onWorkerError'],
        ServerEvent::ON_WORKER_START => [\Ody\Server\ServerCallbacks::class, 'onWorkerStart'],
    ],

    'ssl' => [
        'ssl_cert_file' => null ,
        'ssl_key_file' => null ,
    ] ,

    /**
     * Configure what directories or files must be
     * watched for hot reloading.
     */
    'watcher' => [
        'App',
        'config',
        'database',
        'composer.lock',
        '.env',
    ]
];
